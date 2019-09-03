<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\CashLogModel;
use app\component\model\CashModel;
use app\component\model\DyBannersModel;
use app\component\model\UserModel;
use think\Db;

class CashLogic  extends BaseLogic
{
    /**
     * 钱包首页
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cash($data)
    {
        $field = ['uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        //统计用户总收入，总余额，可提现余额，牵线红包总额，任务红包总额
        //总的牵线红包
        $qx_cash = (new QxCommissionLogic())->sum(['uid'=>$uid,'status' =>1],'money');
        //总的任务红包
        $task_cash = (new CommissionLogic())->sum(['uid' => $uid,'status' => 1],'money');
        //总的语音红包
        $voice_cash = (new VoiceCommissionLogic())->sum(['uid' => $uid,'status' =>1],'money');
        //总收入
        $total_cash = $qx_cash + $task_cash + $voice_cash;

        //已提现总额
        $withdraw = (new CashLogic())->sum(['uid' => $uid,'status' => 2],'money');

        //总的余额
        $res = (new UserLogic())->getInfo(['id'=>$uid]);
        //获取该用户申请的提现总额
        $result = (new CashLogic())->getSumByuid($uid);
        //可提现总金额
        $allow_cash = $res['money']  - $result ;

        $shuju = [
            'total_cash' => $total_cash,    //总收入
            'total_balance' => $res['money'], //总余额
            'total_allow' => $allow_cash,   //可提现余额
            'total_withdraw' => $withdraw,   //已提现总额
            'qx_cash' => $qx_cash,  //牵线红包总额
            'task_cash' => $task_cash //任务红包总额
        ];
        return ApiReturn::success('success',$shuju);


    }

    public function getListss($where,$pagesize,$order,$start,$end)
    {
        $query = Db::table('cash')->alias('c')
            ->join('user u','c.uid=u.id','left')
            ->field('c.*,u.username,u.money total_money,u.status user_status')
            ->where($where)
            ->order($order);
        if(!empty($start)){
            $query = $query->where('c.create_time','>',strtotime($start));
        }
        if(!empty($end))
        {
            $query = $query->where('c.create_time','<',strtotime($end));
        }
        $count = Db::table('cash')->alias('c')->where($where)->count();
        $lists = $query->fetchSql(false)->paginate($pagesize);
        $page = $lists->render();
        return ['page'=>$page,'list'=>$lists,'count'=>$count];
    }

    /**
     * 提现申请
     * @param $data
     * @return array|mixed|string
     */
    public function addCash($data)
    {
        Db::startTrans();
        try{
            $field = ['uid','money'];
            $res = $this->checkFields($field,$data);
            if($res !== ENABLE){
                return $res;
            }
            $uid = $data['uid'];
            $money = $data['money'];
            //判断数据格式
            if(!is_numeric($money)||strpos($money,".")!==false){
                return ApiReturn::error('提现金额必须为整数');
            }
            //判断提现金额是否大于最小提现金额1元
            if($money < 100){
                return ApiReturn::error('提现金额不能小于最低提现标准');
            }


            //判断当前用户状态是否被禁用
            $userInfo = (new UserLogic())->getInfo(['id'=>$uid],false,'id,status');
            if($userInfo['status'] != 1){
                return ApiReturn::error('用户已被禁用');
            }

            //判断用户上一条提现申请时间
            $info = (new CashLogic())->getInfo(['uid' => $uid,'status' => 0],'id desc','id,create_time');
            $intval_time = time() - 60;
            if($info['create_time'] >= $intval_time){
                return ApiReturn::error('每分钟只能提现一次');
            }

            //获取用户的总余额和可提现余额
            $cash= $this->totalCash($uid);
            //判断当前提现金额是否小于可提现余额
            if($money > $cash['allow_cash']){
                return ApiReturn::error('可提现余额不足');
            }

            $map = [
                'uid' => $uid,
                'money' => $money,
            ];
            $res = (new CashLogic())->save($map);
            Db::commit();
            return ApiReturn::success('提现申请成功');

        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('提现申请失败，请重试！');
        }



    }

    /**
     * @param $uid
     * @return array
     */
    public function totalCash($uid)
    {
        //总的余额
        $res = (new UserLogic())->getInfo(['id'=>$uid]);
        //获取该用户申请的提现总额
        $result = (new CashLogic())->getSumByuid($uid);
        //可提现总金额
        $allow_cash = $res['money']  - $result ;
        return ['total_cash'=> $res['money'],'allow_cash'=> $allow_cash];
    }

    public function setStatus($status,$id)
    {
        Db::startTrans();
        try{
            if($status == 1){
                $status = 2;
            }else{
                $status = 1;
            }
            //更新该提现申请为体现成功
            $model = new CashModel();
            $res = $model->save(['status'=>$status],['id'=>$id]);
//            //扣除该用户的可提现余额
            $cashInfo = Db::table('cash')->where('id',$id)->find();
            $result = Db::table('user')->where('id',$cashInfo['uid'])->setDec('money',$cashInfo['money']);
            Db::commit();
            return $result;
        }catch (\ Exception $e){
            Db::rollback();
        }

    }

    /**
     * 用户提现记录
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Collection|\think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashList($data)
    {
        $field = ['uid','pagesize','pageNo'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }

        $pageSize = $data['pagesize'];
        $pageNo = $data['pageNo'];
        $uid = $data['uid'];
        $query = Db::table('cash')->alias('c')
            ->where(['c.uid'=>$uid])
            ->order('c.update_time desc');
        $offSet = $this->getOffset($pageNo,$pageSize);
        $query = $query->limit($offSet,$pageSize)->select();

        return $query;
    }

    public function getSumByuid($uid)
    {
        $res = Db::table('cash')->where(['uid'=>$uid,'status'=>1])->sum('money');
        return $res;
    }



    public function money_sum($start,$end,$status='')
    {
        $query = Db::table('cash');
        if(!empty($start)){
            $query = $query->where('create_time','>',strtotime($start));
        }
        if(!empty($end))
        {
            $query = $query->where('create_time','<',strtotime($end));
        }
        if(!empty($status)){
            $query = $query->where('status',$status);
        }
        $query = $query->sum('money');
        return $query;
    }

}

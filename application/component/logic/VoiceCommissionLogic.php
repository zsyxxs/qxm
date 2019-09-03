<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use think\Db;

class VoiceCommissionLogic  extends BaseLogic
{

    /**
     * 获取语音红包
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function voice_commission($data)
    {
        $field = ['uid','t_id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $t_id = $data['t_id'];
        unset($data['s']);

        $t_info = (new TaskLogic())->getInfo(['id'=>$t_id],false,'id,p_id,uid,visitor_ids');
        $array = explode(',',$t_info['visitor_ids']);
        if(!in_array($uid,$array)){
            return false;
        }

        //判断该用户是否已经得到过该语音红包
        $res = (new VoiceCommissionLogic())->getInfo($data);
        if($res){
            return ApiReturn::error('已获得过该语音红包');
        }
        Db::startTrans();
        try{
            //获取该任务已经发放的红包总和以及已经发放红包个数
            $total_money = (new VoiceCommissionLogic())->sum(['t_id'=>$t_id,'status' => 1,'type'=>1],'money');
            $count = (new VoiceCommissionLogic())->count(['t_id'=>$t_id,'status' => 1,'type'=>1]);
            if($count >= 3){
                return ApiReturn::error('红包已领完');
            }
//            //给该用户发放红包
//            if($count == 2) {
//                //第三个红包
//                //只剩下一个红包，直接用红包总额减去已发放的红包
//                $money = intval(300 - $money_count);
//            }elseif($count == 1) {
//                //第二个红包
//                //从剩余的红包金额中，随机一个红包金额发放
//                $max = (300 - $money_count)  ;
//                $max = intval($max);
//                $money = rand(1,$max);
//            }else {
//                //第一个红包
//                //随机一个红包金额发放
//                $money = rand(1,200);
//            }

            //计算随机红包金额
            $total = 300;
            $num = 3;
            $min = 1;
            $money = $this->get_rand_money($total,$total_money,$num,$count,$min);

            //发放到用户余额，并添加一条语音红包记录
            (new UserLogic())->setInc(['id'=>$uid],'money',$money);
            (new VoiceCommissionLogic())->save([
                'uid' => $uid,
                't_id' => $t_id,
                'money' => $money
            ]);

            Db::commit();
            return ApiReturn::success('恭喜获得红包',$money);

        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('红包获取失败，请重试');
        }





    }


    /**
     * 听取语音获取随机红包
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function random_voice_commission($data)
    {
        $field = ['uid','t_id','t_uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];//听语音uid
        $t_id = $data['t_id'];//任务id
        $t_uid = $data['t_uid'];//执行任务用户uid

        $t_info = (new TaskLogic())->getInfo(['id'=>$t_id],false,'id,p_id,uid,visitor_ids');
        if(empty($t_info)){
            return false;
        }
//        return $t_info;
        $str_ids = $t_info['uid'].','.$t_info['visitor_ids'];
        $array = explode(',',$str_ids);
//        dump($t_info);
//        dump($array);
        if(in_array($uid,$array)){
            return false;
        }

        //判断该用户是否选择的自身标签与该任务用户选择的喜欢标签有重复
        $userInfos = (new UserLogic())->getLists(['id' => array('in',[$uid,$t_uid])],false,'id,flag_user,flag_like');
        $flag_user = [];
        $flag_like = [];
        foreach ($userInfos as $v){
            if($v['id'] == $uid){
                $flag_user = explode(',',$v['flag_user']);
             }else{
                $flag_like = explode(',',$v['flag_like']);
            }
        }
        //判断两者是否有交集
        $res = array_intersect($flag_user,$flag_like);
        if(empty($res)){
            return ApiReturn::error('标签不匹配');
        }

        //该任务的随机语音红包发放个数
        $count = (new VoiceCommissionLogic())->count(['t_id'=>$t_id,'status'=>1,'type'=>2]);
        if($count >= 10){
            return ApiReturn::error('该语音红包已领完');
        }

        //获取该任务的语音红包发放记录
        $list = (new VoiceCommissionLogic())->getLists(['t_id' => $t_id,'status' =>1]);
        //统计已经领取该任务语音红包的人员ids和已经发放的随机语音红包总额

        $total_money = 0;//已发放的随机红包总额
        foreach ($list as $v){
            if($v['uid'] == $uid){
                //该用户已经领取过语音红包
                return ApiReturn::error('已领取过该红包');
            }
            if($v['type'] == 2){
                $total_money += $v['money'];
            }
        }

        //计算随机红包金额
        $total = 500;
        $num = 10;
        $min = 1;
        $money = $this->get_rand_money($total,$total_money,$num,$count,$min);
//        $total = 1000 - $total_money;           //剩余红包总金额
//        $min = 1;                               //每个红包最小金额
//        $num = 10 - $count;                     //还剩下红包数
//
//        if($num > 1){
//            $safe_total = floor(($total-$num*$min)/$num);//随机安全上限
//            $money = mt_rand($min,$safe_total);
//        }else{
//            $money = $total;
//        }

        Db::startTrans();
        try{
            //发放到用户余额，并添加一条语音红包记录
            (new UserLogic())->setInc(['id'=>$uid],'money',$money);
            (new VoiceCommissionLogic())->save([
                'uid' => $uid,
                't_id' => $t_id,
                'money' => $money,
                'type' => 2
            ]);
            Db::commit();
            return ApiReturn::success('红包领取成功',$money);
        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('红包领取失败');
        }

    }

    /**
     * @param $total   //红包总金额
     * @param $money_count  //已发红包总金额
     * @param $num  //总的分红包人数
     * @param $count  //已获取红包人数
     * @param $min   //每个红包最小金额
     * @return int $money  //此次红包金额
     */
    public function get_rand_money($total,$money_count,$num,$count,$min)
    {
        //计算随机红包金额
        $total = $total - $money_count;           //剩余红包总金额
//        $min = 1;                               //每个红包最小金额
        $num = $num - $count;                     //还剩下红包数

        if($num > 1){
            $safe_total = floor(($total-$num*$min)/$num);//随机安全上限
            $money = mt_rand($min,$safe_total);
        }else{
            $money = $total;
        }
        return $money;
    }



}

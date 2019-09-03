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

class ComplainLogic  extends BaseLogic
{

    /**
     * 动态投诉
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function complain($data)
    {
        $field = ['uid','d_id','reason'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $d_id = $data['d_id'];
        $reason = $data['reason'];

        Db::startTrans();
        try{
            //判断该用户是否已经投诉过该动态
            $res = (new ComplainLogic())->getInfo(['d_id' => $d_id,'uid' => $uid]);
            if($res){
                return ApiReturn::error('已投诉');
            }
            $res = (new ComplainLogic())->save($data);
            //判断该动态是否已经因投诉而删除
            $dynamicInfo = (new DynamicLogic())->getInfo(['id'=>$d_id],false,'id,is_complain');
            if($dynamicInfo['is_complain'] == 0){
                //统计该动态，该投诉原因被投诉的次数
                $count = (new ComplainLogic())->count(['d_id'=>$d_id,'reason'=>$reason,'status' => 1]);
                if($count >= 3){
                    //删除该动态
                    $res = (new DynamicLogic())->save(['status' => 0,'is_complain' => 1],['id'=>$d_id]);
                    //用户被投诉次数加1
                    $com_user = (new DynamicLogic())->getInfo(['id'=>$d_id],false,'id,uid');
                    (new UserLogic())->setInc(['id'=>$com_user['uid']],'complain_num',1);
                }
            }

            Db::commit();
            return ApiReturn::success('投诉成功');

        }catch(\Exception $e){
            Db::rollback();
            return ApiReturn::error('投诉失败,请重试');
        }




    }

    /**
     * 投诉原因列表
     * @return array
     */
    public function lists()
    {
        $res = (new ComplainReasonLogic())->getLists(['status'=>1]);
        return ApiReturn::success('success',$res);
    }








}

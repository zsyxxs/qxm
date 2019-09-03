<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;



use app\component\logic\DyBannersLogic;
use app\component\logic\DynamicsLogic;
use app\component\logic\DynamicsPointLogic;
use app\component\logic\ImgsLogic;
use app\component\logic\UserLogic;

class DynamicsApiHelper
{
    /**
     * @param $img_url
     * @return false|int|mixed
     */
    public function dynaUploadsImg($img_url)
    {
        $res = (new ImgsLogic())->add($img_url);
        return $res;
    }

    /**
     * @param $uid
     * @param $title
     * @param $pid
     * @param $imgs
     * @return false|int
     */
    public function addDynamis($uid,$title,$pid,$imgs,$type)
    {
        //获取改用户的微信信息
        $result = (new UserLogic())->getInfoByUid($uid);
        $data = [
            'uid' => $uid,
            'title' => $title,
            'weixin' => $result['username'],
            'logo' => $result['logo'],
            'pid' => $pid,
            'imgs' => $imgs,
            'type' => $type
        ];
        $res = (new DynamicsLogic())->add($data);
        $list = [];
        if($pid){
            //返回该条动态的全部评论
            $list = (new DynamicsLogic())->getComments($pid);
        }
        return ['status' => $res,'list' =>$list];
    }

    /**
     * @param $uid
     * @return array
     */
    public function dynamicsList($uid,$pagesize,$pageNo)
    {
        $lists = (new DynamicsLogic())->getListsByUid($uid,$pagesize,$pageNo);
        return $lists;
    }

    /**
     * @param $id
     * @return false|int
     */
    public function delDynamic($id)
    {
        $res = (new DynamicsLogic())->del($id);
        return $res;
    }

    public function updateDynamicPoint($d_id,$uid,$status)
    {
        $data = [
            'd_id' => $d_id,
            'uid' => $uid,
            'status' => $status
        ];
        $res = (new DynamicsPointLogic())->updateDynamicPoint($data);
        return $res;
    }

    public function getDynamicPoint($d_id,$uid)
    {
        $data = [
            'd_id' => $d_id,
            'uid' => $uid
        ];
        $res = (new DynamicsPointLogic())->getInfo($data);
        return $res;
    }

    public function backGround($uid,$img_id)
    {
        $res = (new DyBannersLogic())->backGround($uid,$img_id);
        return $res;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;



use app\component\logic\ChatLogic;
use app\component\model\ChatModel;
use think\Db;

class ChatApiHelper
{
    public function addInfo($uid,$a_uid,$message,$p_id,$type,$is_read,$length)
    {
        $data = [
            'uid' => $uid,
            'a_uid' => $a_uid,
            'message' => $message,
            'p_id' => $p_id,
            'type' => $type,
            'is_read' => $is_read,
            'length' => $length
        ];
        $res = (new ChatLogic())->addInfo($data);
        //将第一条内容变为显示
        $result = (new ChatModel())->save(['status'=>1],['id'=>$p_id]);
        return $result;
    }

    public function index($uid)
    {
        $res = (new ChatLogic())->index($uid);
        return $res;
    }

    public function del($id)
    {
        $res = (new ChatLogic())->del($id);
        return $res;
    }

    public function user_index($uid,$c_uid)
    {
        $res = (new ChatLogic())->user_index($uid,$c_uid);
        return $res;
    }

    public function ajax($id,$last_id,$uid)
    {
        $res = (new ChatLogic())->ajax($id,$last_id,$uid);
        return $res;
    }

    public function detail($id)
    {
        $res = (new ChatLogic())->detail($id);
        return $res;
    }

    public function is_read($id,$uid)
    {
        $res = (new ChatLogic())->is_read($id,$uid);
        return $res;
    }

    public function getReadNum($uid)
    {
        $res = (new ChatLogic())->getReadNum($uid);
        return $res;
    }


}
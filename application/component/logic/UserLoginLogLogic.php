<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/9
 * Time: 10:04
 */
namespace app\component\logic;

use app\component\model\UserLoginLogModel;
use think\Db;

class UserLoginLogLogic
{
    /**
     * 获取添加用户登录数据并返回token
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function save($data, $is_update=0)
    {
        $model = new UserLoginLogModel();
        if($is_update){
            //更新
            $id = $model->where("uid", $data['uid'])->order("update_time DESC")->value('id');
            if($id){
                $res = $model->save($data, ['id'=>$id]);
                if($res){
                    $times = $model->where("uid", $data['uid'])->order("update_time DESC")->value('update_time');
                    return aesEn($data['uid'].$data['ip'].$times);
                }
            }
        }
        //新增
        $res = $model->save($data);
        if($res){
            $times = $model->where("uid", $data['uid'])->order("update_time DESC")->value('update_time');
            return aesEn($data['uid'].$data['ip'].$times);
        }
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\CashModel;
use app\component\model\ComplainReasonModel;
use app\component\model\DyBannersModel;
use app\component\model\OrderModel;
use app\component\model\UserModel;
use think\Db;

class ComplainReasonLogic  extends BaseLogic
{
    public function addInfo($data)
    {
        $data =  $data['data'];
        unset($data['file']);
        $model = new ComplainReasonModel();
        $res = $model->save($data);
        if($res){
            return '添加成功';
        }else{
            return '添加失败';
        }
    }

    public function updateInfo($data)
    {
        $data = $data['data'];
        $id = $data['id'];
        unset($data['id']);
        unset($data['file']);
        $model = new ComplainReasonModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }


}

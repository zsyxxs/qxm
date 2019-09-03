<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\MessageModel;
use app\component\model\MsgLogModel;
use think\Db;

class MsgLogLogic  extends BaseLogic
{
    public function add($data)
    {
        $model = new MsgLogModel();
        $res = $model->save($data);
    }







}

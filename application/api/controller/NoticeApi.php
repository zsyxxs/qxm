<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/3/8
 * Time: 11:30
 */

namespace app\api\controller;


use app\api\helper\ApiReturn;

use app\component\logic\NoticeLogic;

class NoticeApi extends BaseApi
{
    /**
     * 系统公告
     * @return array
     */
    public function notice()
    {
        $map = ['status' => ENABLE];
//        $res = (new NoticeLogic())->getInfo($map, $order = 'id desc');
        $res = (new NoticeLogic())->getLists($map, $order = 'id desc','id,content,status,create_time,update_time');
        return ApiReturn::success('success',$res);
    }
}
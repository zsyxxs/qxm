<?php
/**
 * 微信网页授权类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;




use app\component\logic\ComplainLogic;


class ComplainApi extends BaseApi
{

    /**
     * 投诉动态
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function complain()
    {
        $data = $_REQUEST;
        $res = (new ComplainLogic())->complain($data);
        return $res;
    }


    /**
     * 投诉原因列表
     * @return array
     */
    public function lists()
    {
        $res = (new ComplainLogic())->lists();
        return $res;
    }













}
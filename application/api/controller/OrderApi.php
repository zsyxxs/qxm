<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/7/2
 * Time: 17:51
 */

namespace app\api\controller;



use app\component\logic\OrderLogic;

class OrderApi extends BaseApi
{

    /**
     * 添加一条支付订单，并返回订单id，即订单号
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_order()
    {
        $data = $_REQUEST;
        $res = (new OrderLogic())->add_order($data);
        return $res;
    }





}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;




use app\component\logic\GoodsLogic;
use app\component\logic\OrderLogic;

class OrderApiHelper
{
    /**
     * @param $id
     * @return array
     */
    public function details($id)
    {
        //订单信息
        $orderInfo = (new OrderLogic())->getDetails($id);
        //该订单包含的商品信息
        $goods_ids = $orderInfo['goods_id'];
        $mount = $orderInfo['mount'];
        $goodsInfo = (new GoodsLogic())->getGoods($goods_ids,$mount);
        return ['orderInfo'=>$orderInfo,'goodsInfo'=>$goodsInfo];
    }

    public function updateOrderStatus($id,$type,$invite_id,$invite_uid)
    {
        //0取消订单，1待付款，2已付款待发货，3待收货，4已收货交易成功，5交易关闭，6售后，7删除订单
        if($type == 0){
            //取消订单
            $status = 0;
        }elseif ($type == 1){
            //待付款
            $status = 1;
        }elseif ($type == 2){
            //付款成功待发货
            $status = 2;
        }elseif ($type == 3){
            //待收货
            $status = 3;
        }elseif ($type == 4){
            //已收货交易完成
            $status = 4;
        }elseif ($type == 5){
            //交易关闭
            $status = 5;
        }elseif ($type == 6){
            //售后
            $status = 6;
        }elseif ($type == 7){
            //删除订单
            $status = 7;
        }
        $res = (new OrderLogic())->updateOrderStatus($id,$status,$invite_id,$invite_uid);
        return $res;
    }

    public function OrderCountByStatus($uid)
    {
        //待付款数量
        $status = 1;
        $obligations = (new OrderLogic())->count($uid,$status);
        //待发货
        $status = 2;
        $delivery = (new OrderLogic())->count($uid,$status);
        //待收货
        $status = 3;
        $receive = (new OrderLogic())->count($uid,$status);
        //售后
        $status = 6;
        $after = (new OrderLogic())->count($uid,$status);
        return ['obligations' =>$obligations,'delivery'=>$delivery,'receive'=>$receive,'after'=>$after ];

    }

}
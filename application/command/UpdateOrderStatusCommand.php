<?php
/**
 * 定时更改订单的收货状态,并发放该订单所需支付的佣金
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20
 * Time: 18:25
 */

namespace app\command;


use app\component\logic\GoodsCommissionLogic;
use app\component\model\GoodsCommissionModel;
use app\component\model\OrderPayModel;
use app\component\model\OrdersModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class UpdateOrderStatusCommand extends Command
{
    protected function configure()
    {
        $this->setName('updateOrderStatus:check')
            ->setDescription('mingpian check order status');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        $res = $this->updateOrderStatus();//更改订单表中已发货时间超出指定时间的订单为已收货
        dump($res);
    }

    protected function updateOrderStatus()
    {
        //获取订单表中状态为已经发货，且发货时间达到15天的订单总数
//        $time = 15*24*60*60;
        $time = 60;
        $intval_time = time() - $time;
        $list = Db::table('orders')->where('status',3)->where('pay_time','<=',$intval_time)->select();
        foreach ($list as $k=>$v)
        {
            //发货已超15天，更改为自动收货
            $data = [
                'status' => 4,
                'pay_time' => time()
            ];
            $res = (new OrdersModel())->save($data,['id'=>$v['id']]);
            //判断该订单是否需要发放佣金
            if($v['is_commission'] == 1){
                //需要发放佣金
                $result = (new GoodsCommissionLogic())->sendCommission($v['id']);
            }
            //将该订单的佣金记录更改为已发放(包括个人和平台)
            $result = (new GoodsCommissionModel())->save(['status'=>2],['order_id'=>$v['id']]);
            //将该订单的收益发放给商家
            //订单收益 = 总订单价格 - (给平台佣金 + 个人佣金)
            $result = (new GoodsCommissionLogic())->sendOrderCommission($v);
            //将该订单付款记录表中更新为已发放
            $res = (new OrderPayModel())->save(['status'=>2],['order_id'=>$v['id']]);

        }
        return 'update order status success';



    }


}
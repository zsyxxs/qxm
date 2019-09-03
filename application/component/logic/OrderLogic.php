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
use app\component\model\DyBannersModel;
use app\component\model\OrderModel;
use app\component\model\UserModel;
use think\Db;

class OrderLogic  extends BaseLogic
{

    /**
     * 添加订单
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_order($data)
    {
        $fields = ['uid','money'];
        $res = $this->checkFields($fields,$data);
        if($res !== ENABLE){
            return $res;
        }
        $userInfo = (new UserLogic())->getInfo(['id' => $data['uid']]);
        if(empty($userInfo)){
            return ApiReturn::error('用户不存在');
        }

        Db::startTrans();
        try{
            //添加一条订单记录
            $data['create_time'] = time();
            $id = (new OrderLogic())->getInsertId($data);
            //生成随机订单编号
            $order_num = $this->order_num();
            (new OrderLogic())->save(['order_num'=>$order_num],['id'=>$id]);

           Db::commit();
            return ApiReturn::success('订单添加成功',['id' => $id,'order_num' => $order_num]);

        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('订单添加失败');
        }


    }

}

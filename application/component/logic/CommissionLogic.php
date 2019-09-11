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

class CommissionLogic  extends BaseLogic
{

    /**
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_red_packet($data)
    {
        $field = ['uid','pageNo','pagesize'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $map = [
            'uid' => $data['uid']
        ];
        $order = 'update_time desc';
        $field = 'c.id,c.uid,c.money,c.level,c.status,c.create_time,c.update_time,u.username,u.logo';
        $query = Db::table('commission')
            ->alias("c")
            ->join('user u', 'u.id=c.q_uid','left')
            ->where($map)
            ->order($order)
            ->field($field);
        $offset = $this->getOffset($data['pageNo'],$data['pagesize']);
        $list = $query->limit($offset,$data['pagesize'])->select();
//        $list = (new QxCommissionLogic())->queryPage($map,$order,$field,$data['pageNo'],$data['pagesize']);
        return ApiReturn::success('success',$list);
    }

}

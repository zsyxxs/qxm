<?php
/**
 * 微信网页授权类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;




use app\component\logic\CashLogic;
use app\component\logic\CommissionLogic;
use app\component\logic\QxCommissionLogic;

class CashApi extends BaseApi
{
    /**
     * 钱包首页
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cash()
    {
        $data = $_REQUEST;
        $res = (new CashLogic())->cash($data);
        return $res;
    }

    /**
     * 提现申请
     * @return array
     */
    public function addCash()
    {
        $data = $_REQUEST;
        $res = (new CashLogic())->addCash($data);
        return $res;
    }

    /**
     * 用户提现记录
     * @return array|false|\PDOStatement|string|\think\Collection|\think\db\Query
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashList()
    {
        $data = $_REQUEST;
        $res = (new CashLogic())->cashList($data);
        return $res;
    }

    /**
     * 牵线红包记录
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function qx_red_packet()
    {
        $data = $_REQUEST;
        $res = (new QxCommissionLogic())->qx_red_packet($data);
        return $res;
    }


    /**
     * 任务佣金记录
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_red_packet()
    {
        $data = $_REQUEST;
        $res = (new CommissionLogic())->task_red_packet($data);
        return $res;
    }













}
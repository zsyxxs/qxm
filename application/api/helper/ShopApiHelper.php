<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;


use app\component\logic\BannersLogic;
use app\component\logic\ShopLogic;


class ShopApiHelper
{
    /**
     * @param $uid
     * @param $pagesize
     * @param $pageno
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($uid,$pagesize,$pageno)
    {
        //商品列表
        $lists = (new ShopLogic())->index($uid,$pagesize,$pageno);

        return ['lists'=>$lists];
    }

    /**
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function queryDetail($id)
    {
        $res = (new ShopLogic())->queryDetail($id);
        return $res;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;


use app\component\logic\CategoryLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\UserLogic;

class CardshopApiHelper
{
    /**
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function queryCategorys($id)
    {
        $res = (new CategoryLogic())->queryCategorys($id);
        return $res;
    }

    public function queryUsers($id)
    {
        //获取该分类下的所有名片用户ids
        $ids = (new ManagerLogic())->queryUsersIds($id);
        //获取当前分类下的所有名片用户信息
        $res = (new UserLogic())->queryUserLists($ids);
        return $res;
    }

}
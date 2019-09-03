<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/3/8
 * Time: 11:30
 */

namespace app\api\controller;


use app\component\logic\BannerApiLogin;
use app\component\logic\BannersLogic;

class BannerApi extends BaseApi
{
    /**
     * 系统banners图
     * @return array
     */
    public function banners()
    {
        $position = 1;
        $pagesize = 10;
        $pageNo = 1;
        $res = (new BannersLogic())->queryBy($position,$pagesize,$pageNo);
        return $res;
    }
}
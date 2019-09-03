<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;


use app\component\logic\BannersLogic;
use app\component\logic\InformationLogic;
use app\component\logic\MembersLogic;
use app\component\logic\NewsLogic;
use app\component\logic\ShopLogic;


class PlatformApiHelper
{
    public function banners($position)
    {
        $res = (new BannersLogic())->banners($position);
        return $res;
    }

    public function news($type,$pageno,$pagesize)
    {
        $res = (new NewsLogic())->news($type,$pageno,$pagesize);
        return $res;
    }

    public function members()
    {
        $res = (new MembersLogic())->members();
        return $res;
    }

    public function information()
    {
        $res = (new InformationLogic())->information();
        return $res;
    }

    public function newDetail($id)
    {
        $res = (new NewsLogic())->newDetail($id);
        return $res;
    }

    public function allBanners()
    {
        $res = (new BannersLogic())->allBanners();
        return $res;
    }

}
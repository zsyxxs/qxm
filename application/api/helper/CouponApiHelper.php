<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;



use app\component\logic\CouponLogic;
use app\component\logic\UserCouponLogic;

class CouponApiHelper
{
    public function saleCoupon($uid,$c_id)
    {
        $res = (new UserCouponLogic())->saleCoupon($uid,$c_id);
        return $res;
    }

    public function coupon($pageno,$pagesize)
    {
        $res = (new CouponLogic())->coupon($pageno,$pagesize);
        return $res;
    }

    public function userCoupon($uid,$pageno,$pagesize)
    {
        $res = (new UserCouponLogic())->userCoupon($uid,$pageno,$pagesize);
        return $res;
    }

}
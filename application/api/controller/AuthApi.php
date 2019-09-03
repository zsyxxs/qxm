<?php
/**
 * 微信网页授权类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;




use app\component\interfaces\auth\api\WxAuthresultApi;

class AuthApi extends BaseApi
{

    /**
     * 授权页面
     * @return array|string
     */
    public function oauth()
    {
        $res = (new WxAuthresultApi())->oauth();
        return $res;

    }

    /**
     * 微信授权回调获取用户信息方法
     * @param $data
     * @return array|bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login()
    {
        $data = $_REQUEST;
//        dump($data);
//        return $data;
        $res = (new WxAuthresultApi())->userInfo($data);
        return $res;
    }














}
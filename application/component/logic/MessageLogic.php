<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\MessageModel;
use think\Db;

class MessageLogic  extends BaseLogic
{
    /**
     * 判断一分钟内该手机号码是否发送过短信
     * @param $phone
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfoByPhone($phone)
    {
        $intval_time = (time() -60);
        $res = Db::table('message')
            ->where('phone',$phone)
            ->where('create_time','>',$intval_time)
            ->order('id desc')
            ->limit(1)
            ->find();
        return $res;
    }


    /**
     * @param $code
     * @param $phone
     * @return false|int
     */
    public function addInfo($code,$phone)
    {
        $data = [
            'code' => $code,
            'phone' => $phone
        ];
        $res = (new MessageModel())->save($data);
        return $res;
    }

    /**
     * 验证手机号验证码是否正确
     * @param $phone
     * @param $code
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkPhoneCode($phone,$code)
    {
        $res = Db::table('message')->where(['phone'=>$phone])->limit(1)->order('id desc')->find();
        if($code != $res['code']){
            //验证码错误
            return false;
        }
        return true;
    }








}

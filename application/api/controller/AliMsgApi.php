<?php
/**
 * 阿里云短信接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;


use app\api\helper\ApiReturn;
use app\component\interfaces\message\alidymsgapi\api_demo\SmsDemo;
use app\component\logic\MessageLogic;
use app\component\logic\MsgLogLogic;
use think\Validate;

class AliMsgApi extends BaseApi
{
    /**
     * 发送短信验证码
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendMsg()
    {
        $param = input();
        $validate = new Validate([
            'phone|电话'         => 'require|regex:/^1[34578]\d{9}$/',
        ]);
        if (!$validate->check($param)) {
            return ApiReturn::error('请输入正确手机号');
        }
        $phone = $param['phone'];
        //判断该手机号发送验证码时间间隔
        $info = (new MessageLogic())->getInfoByPhone($phone);
        if($info){
            //60秒内发送过，则不能再发
            return ApiReturn::error('短信发送频繁');
        }else{
            //没有发送过短信
            $code = rand(1111,9999);

            $result = (new SmsDemo())->sendSms($phone,$code);

            if($result->Code !== 'OK'){
                $data = [
                    'phone' => $phone,
                    'code' => $code,
                    'result' => json_encode($result,JSON_UNESCAPED_UNICODE)
                ];
                (new MsgLogLogic())->add($data);
                return ApiReturn::error('短信发送失败');
            }
            $res = (new MessageLogic())->addInfo($code,$phone);
            return ApiReturn::success('短信发送成功',$code);
        }

    }



}
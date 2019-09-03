<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/16
 * Time: 10:41
 */

namespace app\component\interfaces\message\api;


class AlimsgresultApi extends AlimsgUrl
{
    public function sendMsg()
    {
        $sendUrl = $this->sendUrl;
        $accessKeyId = $this->accessKeyId;

        $data = [
            'PhoneNumbers' => '15858629682',
            'SignName' => '阿里云短信测试专用',   //短信签名名称
            'TemplateCode' => 'SMS_117850006',  //短信模板ID
            'AccessKeyId' => 'LTAIxuB7pB8nzuZE',      //主账号AccessKey的ID
            'Action' => 'SendSms',              //系统规定参数
            'Version' => '2017-05-25',  //版本号
            'Timestamp' => gmdate("Y-m-d\TH:i:s\Z"),     //时间戳
            'Format' => 'json',
            'RegionId' => 'cn-hangzhou',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => time(),
            'SignatureVersion' => '1.0',
        ];
        $result = (new AlimsgvalueApi())->get_result_get($sendUrl,$data);
        return $result;
    }
}
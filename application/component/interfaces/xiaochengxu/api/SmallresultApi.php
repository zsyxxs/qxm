<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 14:30
 */

namespace app\component\interfaces\xiaochengxu\api;

class SmallresultApi extends SmallUrl
{
    public function index($code)
    {
        $url = $this->code2SessionUrl;
        $data = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'js_code' => $code,
            'grant_type' => $this->grantType
        ];
        $result = (new SmallvalueApi())->get_result_get($url,$data);
        return json_decode($result);
    }
}
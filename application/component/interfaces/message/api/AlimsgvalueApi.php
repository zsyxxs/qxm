<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/16
 * Time: 10:42
 */

namespace app\component\interfaces\message\api;


class AlimsgvalueApi
{
    public function get_result_get($url,$data)
    {
        foreach ($data as $k => $v){
            $url .= $k.'='.$v.'&';
        }
        $result = $this->http_get($url);
        return $result;
    }

    /**
     * @param $url
     * @return mixed
     */
    public function http_get($url)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置请求头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        $data = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $data;    //返回json对象
    }
}
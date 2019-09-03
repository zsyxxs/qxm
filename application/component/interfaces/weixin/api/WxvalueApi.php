<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:29
 */

namespace app\component\interfaces\weixin\api;


class WxvalueApi
{
    public function get_result_post($url,$data)
    {
        $result = $this->sendUrl($url,$data);
        return $result;
    }

    public function get_result_get($url,$data)
    {
        foreach ($data as $k => $v){
            $url .= $k.'='.$v.'&';
        }
//        return $url;
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

    /**
     * 发送post请求
     * @param $url
     * @param $param
     * @return bool|mixed
     */
    public function sendUrl($url,$param){
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        //设置请求头
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);

        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}
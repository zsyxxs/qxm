<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/21
 * Time: 19:32
 */

namespace app\component\interfaces\wxpay\api;


class WxpayvalueApi
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

    function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,35)};
        }
        return $key;
    }

    /**
     * 生成签名, $KEY就是支付key
     * @return 签名
     */
    public function MakeSign( $params,$KEY){
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);  //参数进行拼接key=value&k=v
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$KEY;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    function appgetSign($Obj,$appwxpay_key)

    {

        foreach ($Obj as $k => $v)

        {

            $Parameters[$k] = $v;

        }

        //签名步骤一：按字典序排序参数

        ksort($Parameters);

        $String = $this->formatBizQueryParaMap($Parameters, false);

        //echo '【string1】'.$String.'</br>';

        //签名步骤二：在string后加入KEY
        if($appwxpay_key){
            $String = $String."&key=".$appwxpay_key;
        }

        //echo "【string2】".$String."</br>";

        //签名步骤三：MD5加密

        $String = md5($String);

        //echo "【string3】 ".$String."</br>";

        //签名步骤四：所有字符转为大写

        $result_ = strtoupper($String);

        //echo "【result】 ".$result_."</br>";

        return $result_;

    }

    //按字典序排序参数
    function formatBizQueryParaMap($paraMap, $urlencode)

    {

        $buff = "";

        ksort($paraMap);

        foreach ($paraMap as $k => $v)

        {

            if($urlencode)

            {

                $v = urlencode($v);

            }

            //$buff .= strtolower($k) . "=" . $v . "&";

            $buff .= $k . "=" . $v . "&";

        }



        if (strlen($buff) > 0)

        {

            $reqPar = substr($buff, 0, strlen($buff)-1);

        }

        return $reqPar;

    }

    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    public function ToUrlParams( $params ){
        $string = '';
        if( !empty($params) ){
            $array = array();
            foreach( $params as $key => $value ){
                $array[] = $key.'='.$value;
            }
            $string = implode("&",$array);
        }
        return $string;
    }

    //将数组转换为xml格式
    function arrayToXml($arr)

    {

        $xml = "<xml>";

        foreach ($arr as $key=>$val)

        {

//            if (is_numeric($val))
//
//            {

            $xml.="<".$key.">".$val."</".$key.">";

//            }

//            else
//
//                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";

        }

        $xml.="</xml>";

        return $xml;

    }

    //xml格式数据解析函数
    function XMLDataParse($data){
        $xml = simplexml_load_string($data,NULL,LIBXML_NOCDATA);
        $array=json_decode(json_encode($xml),true);
        return $array;
    }

    //获取xml里面数据，转换成array
    public function xml2array($xml){
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $data = "";
        foreach ($index as $key=>$value) {
            if($key == 'xml' || $key == 'XML') continue;
            $tag = $vals[$value[0]]['tag'];
            $value = $vals[$value[0]]['value'];
            $data[$tag] = $value;
            return $data;
        }

    }

    /**
 * 调用接口， $data是数组参数
 * @return 签名
 */
    public function http_request($url,$data = null,$headers=array())
    {
        $curl = curl_init();
        if( count($headers) >= 1 ){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * 调用接口， $data是数组参数
     * @return 签名
     */
    public function http_request_certificate($url,$data = null)
    {
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );

        //两个证书（必填，请求需要双向证书。）
        $zs1="/public/certificate/apiclient_cert.pem";
        $zs2="/public/certificate/apiclient_key.pem";
        curl_setopt($ch,CURLOPT_SSLCERT,$zs1);
        curl_setopt($ch,CURLOPT_SSLKEY,$zs2);
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch,CURLOPT_SSLCERT,ROOT_PATH.$zs1); //这个是证书的位置
        curl_setopt($ch,CURLOPT_SSLKEY,ROOT_PATH.$zs2); //这个也是证书的位置

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


}
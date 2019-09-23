<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 14:30
 */

namespace app\component\interfaces\weixin\api;


use app\api\controller\OssApi;
use app\api\helper\ApiReturn;
use app\component\logic\HouseTokenLogic;
use app\component\logic\SendTemplateLogic;
use app\component\logic\TokenLogic;
use think\Db;

class WxresultApi extends WxUrl
{

    public function sendTemplate($data)
    {
        $access_token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

        $data = json_encode($data);
        $result = (new WxvalueApi())->get_result_post($url,$data);
        $result = json_decode($result,true);

        return $result;

    }

    /**
     * @param $mediaId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downloadWeixinFile($mediaId)
    {
        //$mediaId 微信语音mediaId（前端传递过来）
        //微信access_token（这个参数获取省略，不难，自己获取）
        $access_token = $this->get_access_token();
//        $mode = 1; //将amr格式转换为MP3格式
//        $mode = 2; //将amr格式转换为wav格式
//        $mode = 3; //原始amr 文件
        $mode = 4; //将原始speex格式转为wav格式
        if($mode!=4){
            $path = "./uploads/voice/amr";   //保存路径，相对当前文件的路径
            //微信上传下载媒体文件
            $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$mediaId}";
            //文件名称
            $filename = "wxupload_".date('Ymd').time().rand(1111,9999).".amr";
            //下载微信语音并保存
            $this->downAndSaveFile($url,$path."/".$filename);

            //要转换的amr文件地址
            $armFile = "/uploads/voice/amr/".$filename;
        }else{
            $path = "./uploads/voice/speex";   //保存路径，相对当前文件的路径
            $url = "https://api.weixin.qq.com/cgi-bin/media/get/jssdk?access_token={$access_token}&media_id={$mediaId}";
            //文件名称
            $filename = "wxupload_".date('Ymd').time().rand(1111,9999).".speex";
            //下载微信语音并保存
            $this->downAndSaveFile($url,$path."/".$filename);

            //要转换的amr文件地址
            $speexFile = "/uploads/voice/speex/".$filename;
        }
        if($mode==4){
            //转换后的WAV文件保存的文件名
            $wavFilename = "wxupload_".date('Ymd').time().rand(1111,9999).".wav";
            //转换后的MP3文件保存的路径
            $wavFile = "/uploads/voice/wav/".$wavFilename;
            $this->speexTransCodingWav($speexFile, $wavFile);

            //上传到oss
            $res = $this->uploadFile($wavFilename, $wavFile);
        }else if($mode==1){
            //转换后的MP3文件保存的文件名
            $mp3Filename = "wxupload_".date('Ymd').time().rand(1111,9999).".mp3";
            //转换后的MP3文件保存的路径
            $mp3File = "/uploads/voice/mp3/".$mp3Filename;
            $this->amrTransCodingMp3($armFile, $mp3File);
            //上传到oss
            $res = $this->uploadFile($mp3Filename,$mp3File);
        }else if($mode==2){
            //转换后的WAV文件保存的文件名
            $wavFilename = "wxupload_".date('Ymd').time().rand(1111,9999).".wav";
            //转换后的MP3文件保存的路径
            $wavFile = "/uploads/voice/wav/".$wavFilename;
            $this->amrTransCodingWav($armFile, $wavFile);

            //上传到oss
            $res = $this->uploadFile($wavFilename, $wavFile);
        }else{
            //上传到oss
            $res = $this->uploadFile($filename, $armFile);
        }
        return ApiReturn::success('success',$res['info']['url']);
    }

    //根据URL地址，下载文件
    public function downAndSaveFile($url,$savePath){
        ob_start();
        readfile($url);
        $img  = ob_get_contents();
//        dump($img);die;
        ob_end_clean();
        $size = strlen($img);
        $fp = fopen($savePath, 'a');
        fwrite($fp, $img);
        fclose($fp);
    }

    //将微信语音amr格式转换为MP3格式
    public function amrTransCodingMp3($armFile, $mp3File)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'];
        exec("ffmpeg -i ".$dir.$armFile." ".$dir.$mp3File);
        return $mp3File;
    }

    //将微信语音amr格式转换为WAV格式
    public function amrTransCodingWav($armFile, $wavFile)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'];
        exec("ffmpeg -i ".$dir.$armFile." ".$dir.$wavFile);
        return $wavFile;
    }

    //将微信语音speex格式转换为WAV格式
    public function speexTransCodingWav($armFile, $wavFile)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'];
        exec("speex2wav ".$dir.$armFile." ".$dir.$wavFile);
        return $wavFile;
    }

    function uploadFile($mp3Filename,$mp3File)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'].$mp3File;
        $res = (new OssApi())->upload($mp3Filename,$filePath);
        return $res;
    }


















    /**
     * 微信公众号二维码连接
     * @param $scene_str
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function qrcode($scene_str)
    {
        $access_token = $this->get_access_token();
        $url = $this->qrcodeUrl . 'access_token=' . $access_token;

        $expire_seconds = 60*60*24*30;
        $data = [
            'action_name' => 'QR_SCENE',
            'expire_seconds' => $expire_seconds,
            'action_info' => [
                'scene' => [
                    'scene_str' => $scene_str
                ]
            ]
        ];
        $data = json_encode($data);
        $result = (new WxvalueApi())->get_result_post($url,$data);
        $result = json_decode($result,true);
        if(!isset($result['ticket'])){
            return ApiReturn::error('失败，请重试');
        }
        $url = $this->showqrcodeUrl .'ticket='.$result['ticket'];

        $fileName = 'qxm'.time().rand(1111,9999);//拼接文件名称
        $save = "./uploads/pic/".$fileName.'.jpeg';
        //下载微信公众号二维码到服务器
        $this->downAndSaveFile($url,$save);
        $url = config('webUrl.apiUrl'). "/uploads/pic/".$fileName.'.jpeg';

        $filename = "uploads/pic/".$fileName.'.jpeg';
        $url = (new OssApi())->upload($filename,$filename);
        return ApiReturn::success('success',$url['info']['url']);
    }

    /**
     * 微信功能签名验证接口
     * @param $url
     * @return false|string
     */
    public function getSign($url)
    {
        return $this->getSignPackage($url);
    }

    public function getSignPackage($url)
    {
        $jsapiTicket = $this->get_js_api_ticket();
//        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string,
            "jsapiTicket" => $jsapiTicket
        );
//        dump($signPackage);
        return $signPackage;
    }

    /**
     * 调用生成小程序二维码接口
     * @param $page
     * @param $scene
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWXACodeUnlimit($page, $scene)
    {
        $access_token = $this->get_access_token();
        $url = $this->getWXACodeUnlimitUrl . 'access_token=' . $access_token;
        $data = [
            'scene' => $scene,
            'page' => $page,
            'width' => 90,
            'is_hyaline' => true
        ];
        $data = json_encode($data, true);
        $result = (new WxvalueApi())->get_result_post($url,$data);//二进制流

        $newResult = json_decode($result,true);
        if(isset($newResult['errcode']) && $newResult['errcode'] == '40001'){
            //access_token过期，重新请求微信接口
            //调用微信接口，获取access_token，并写入
            $access_token = $this->access_token();
            $url = $this->getWXACodeUnlimitUrl . 'access_token=' . $access_token;
            $data = [
                'scene' => $scene,
                'page' => $page,
                'width' => 90,
                'is_hyaline' => true
            ];
            $data = json_encode($data, true);
            $result = (new WxvalueApi())->get_result_post($url,$data);//二进制流
            $fileName = 'mingpian'.time().rand(1111,9999);//拼接文件名称
            file_put_contents("./uploads/pic/" . $fileName . ".jpeg", $result);
            return "/uploads/pic/" . $fileName . ".jpeg";
        }


        $fileName = 'mingpian'.time().rand(1111,9999);//拼接文件名称
        file_put_contents("./uploads/pic/" . $fileName . ".jpeg", $result);
        return "/uploads/pic/" . $fileName . ".jpeg";

    }

    //发送客服消息
    public function sendTemplateMessage($openId,$formId,$template_id,$data,$page,$id)
    {
        $access_token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
        $shuju = [
            'touser' => $openId,
            'template_id' => $template_id,
            'form_id' => $formId,
            'data' => $data,
            'page' => $page
        ];

        $where = ['data' => json_encode($data,JSON_UNESCAPED_UNICODE)];
        (new SendTemplateLogic())->updateInfo($id,$where);//更新消息发送记录

        $shuju = json_encode($shuju,true);
        $result = (new WxvalueApi())->get_result_post($url,$shuju);
        $newResult = json_decode($result,true);

        if(isset($newResult['errcode']) && $newResult['errcode'] == '40001'){
            //access_token过期，重新请求微信接口
            //调用微信接口，获取access_token，并写入
            $access_token = $this->access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
            $shuju2 = [
                'touser' => $openId,
                'template_id' => $template_id,
                'form_id' => $formId,
                'data' => $data,
                'page' => $page
            ];

            $where = ['data' => json_encode($data,JSON_UNESCAPED_UNICODE)];
            (new SendTemplateLogic())->updateInfo($id,$where);//更新消息发送记录

            $shuju2 = json_encode($shuju2,true);
            $result = (new WxvalueApi())->get_result_post($url,$shuju2);
            return $result;
        }
        return $result;

    }

    /**
     * 调用微信接口，获取access_token
     * @return mixed
     */
    private function access_token()
    {
        //调用微信接口获取access_token
        $url = $this->accessTokenUrl;
        $data = [
            'grant_type' => $this->grantType,
            'appid' => $this->appid,
            'secret' => $this->secret,
        ];
        $result = (new WxvalueApi())->get_result_get($url, $data);
        $result = json_decode($result, true);
        //写入数据库
        $shuju = [
            'token' => $result['access_token'],
            'type' => 1,
            'title_type' => 'weixin',
            'expires_in' => time() + $result['expires_in']
        ];
        $res = (new TokenLogic())->save($shuju);
        return $result['access_token'];
    }

    /**
     * 获取微信access_token，如果数据库保存的已过期，则重新获取
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function get_access_token()
    {
        $info = Db::table('token')->where(['title_type'=>'weixin','type'=>1])->order('id desc')->find();
        $time = time();

        if (!$info) {
            //调用微信接口，获取access_token，并写入
            $result = $this->access_token();
            return $result;
        } else {
            //有记录，判断是否过期
            if ($time > $info['expires_in']) {
                //access_token已过期，重新调用接口获取，并写入
                $result = $this->access_token();
                return $result;
            } else {
                //未过期，返回数据
                return $info['token'];
            }
        }

    }




    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getJsApiTicket()
    {
        $accessToken = $this->get_access_token();
        $url = $this->jsApiTicketUrl;
        $data = [
            'type' => 'jsapi',
            'access_token' => $accessToken,
        ];
        $result = (new WxvalueApi())->get_result_get($url,$data);
        $result = json_decode($result,true);
        if(!isset($result['ticket'])){
            return false;
        }
        //写入数据库
        $title = 'jsapi';
//        $res = $res = (new HouseTokenLogic())->saveInfo($result['ticket'],$title);
        $expire_in = time() + 7000;
        $res = (new TokenLogic())->save(['type' => 2,'title_type' => $title,'token'=>$result['ticket'],'expires_in' => $expire_in]);
        return $res;
    }

    //获取jsapi
    private function get_js_api_ticket()
    {
        $info = Db::table('token')->where(['title_type'=>'jsapi','type' => 2])->order('id desc')->find();
        $time = time();

        if(!$info){
            //调用微信接口，获取access_token，并写入
            $result = $this->getJsApiTicket();
            return $result;
        }else{
            //有记录，判断是否过期
            if($time > $info['expires_in']){
                //access_token已过期，重新调用接口获取，并写入
                $result = $this->getJsApiTicket();
                return $result;
            }else{
                //未过期，返回数据
                return $info['token'];
            }
        }
    }



}
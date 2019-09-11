<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;

use app\api\helper\ApiReturn;
use app\component\interfaces\weixin\api\WxresultApi;
use app\component\interfaces\wxpay\api\WxpayresultApi;
use app\component\logic\BaseLogic;
use app\component\logic\FlagsLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\OrderLogic;
use app\component\logic\TaskLogic;
use app\component\logic\TemplateLogic;
use app\component\logic\UserLogic;
use think\Db;
use Endroid\QrCode\QrCode;


class WeixinApi extends BaseApi
{

    public function getSign()
    {
        $url = $this->_param('url','h5.qxmiao.com');
        $res = (new WxresultApi())->getSign($url);
        return ApiReturn::success('success',$res);
    }

    /**
     * 微信预支付订单
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function unifiedorder()
    {
        $data = $_REQUEST;
        $fields = ['total_fee','openid','order_num','body'];
        $res = (new BaseLogic())->checkFields($fields,$data);
        if($res !== ENABLE){
            return $res;
        }
        $total_fee = intval($data['total_fee']);
        $openid = $data['openid'];
        $order_num = $data['order_num'];
        $body = $data['body'];
//        $total_fee = 1;
//        $openid = 'oaVObwpW2Y2KJMCjLvHHKgW4HSHI';
//        $order_num = '14qxm0R1JWj20190802105633';
//        $body = '测试支付';
//        if($total_fee < 39900){
//            return ApiReturn::error('金额错误');
//        }
        //判断订单是否存在
        $orderInfo = (new OrderLogic())->getInfo(['order_num'=>$order_num]);
        if(empty($orderInfo)){
            return ApiReturn::error('订单号错误');
        }
        //判断用户是否存在
        $userInfo = (new UserLogic())->getInfo(['id'=>$orderInfo['uid']]);
        if(empty($userInfo)){
            return ApiReturn::error('用户不存在');
        }

        $res = (new WxpayresultApi())->Pay($total_fee,$openid,$order_num,$body);

        return $res;

    }


    /**
     * 微信支付回调
     */
    public function notify_url()
    {
//        $res = (new WxpayresultApi())->notify_result();
        $res = (new WxpayresultApi())->notify_results();
    }


    /**
     * 企业向微信用户个人付款
     * @param $cash_id
     * @param $uid
     * @param $money
     * @return mixed
     */
    public function transfers($cash_id,$uid,$money)
    {
        $userInfo = (new UserLogic())->getInfo(['id' => $uid]);
        $open_id = $userInfo['openid'];
        $username = $userInfo['username'];
        $description ='余额提现成功';

        $res = (new WxpayresultApi())->transfers($uid,$cash_id,$open_id,$username,$description,$money);
        return $res;
    }


    /**
     * 下载微信语音文件，并将amr格式转换为mp3格式，上传到阿里云oss
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function downloadWeixinFile()
    {
        $mediaId = $this->_param('mediaId','Fth8sKn6xaEfghzEtPsYGrOBjzoSM_ylT63lYhrgaf1_Xqf4dEGw-7wDFjzBwMeT');
//        $mediaId = $this->_param('mediaId','1237378768e7q8e7r8qwesafdasdfasdfaxss111');
        $res = (new WxresultApi())->downloadWeixinFile($mediaId);
        return $res;

    }

    public function sendTemplate()
    {
        $type = $this->_param('type','1');
        //1：选择完标签给上级发送
        //2：任务点赞发布语音动态
        $uid = $this->_param('uid',27);
        $url = $this->_param('url','http://h5.raydonet.com');
        $appid = config('wxUrl.appid');

       if($type == 1){
           $res = (new TemplateLogic())->sendTaskTemplate($uid,$url,$appid);
           return $res;
       }


    }


    /**
     * 微信公众号二维码
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getQrcodeUrl()
    {
        $scene_str = $this->_param('scene_str','');
        $res = (new WxresultApi())->qrcode($scene_str);
        return $res;
    }


    /**
     * 生成自定义二维码
     * @return array
     */
    public function create_qrcode(){
        $uid = $this->_param('uid','3');
        $url = $this->_param('url',config('webUrl.h5Url'));


        $user = (new UserLogic())->getInfo(['id'=>$uid],false,'id,logo');
        $logo = $user['logo'];
        if(empty($logo)){
            return ApiReturn::error('信息错误请重试');
        }
//        $logo="http://thirdwx.qlogo.cn/mmopen/vi_32/kRcWHa8384Y6CTuHp8UTor5ibGUefvSUicmlv9iajS4Hp16vYAb8DzQcZMKQaQo2sZxhXpacibJq87VxW0ib444yPkw/132";
        //移动文件到框架应用更目录的public/uploads/
        //二维码URL参数
        $filename=code($url);

        //上传到阿里云
        $url = (new OssApi())->upload($filename,$filename);
        return ApiReturn::success('success',$url['info']['url']);


    }



















    /**
     * 获取小程序的二维码
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWXACodeUnlimit()
    {
        $page = $this->_param('page','pages/carteShow/carteShow');
        $scene = $this->_param('scene','224-191');
        $result = (new WxresultApi())->getWXACodeUnlimit($page,$scene);
        return $result;
    }









    //微信退款
    public function refund($uid,$sn)
    {
//        $uid = $this->_param('uid','1');
//        $sn = $this->_param('sn','201903181749395359');
        $info =(new PayLogLogic())->getInfoBySn($sn);
        if(empty($info)){
            return ['data' => '退款订单异常','code' => '-1'];
        }
        $info = json_decode($info['text']);
        $result_code = $info->result_code;
        $return_code = $info->return_code;
        if($result_code == 'SUCCESS' && $return_code == 'SUCCESS'){
            $weixin_id = $info->transaction_id;
            $order_total_fee = $info->total_fee;
            $res = (new WxAuthresultApi())->refund($uid,$weixin_id,$sn,$order_total_fee);
            return ['data' => $res,'code' => '1'];
        }else{
            return ['data' => '退款订单异常','code' => '-1'];
        }

    }


    /**
     * 生成海报
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create_poster(){
        //生成微信公众号二维码地址
        $scene_str = $this->_param('scene_str','test');
        $url = (new WxresultApi())->qrcode($scene_str);
        $background = 'haibao.jpg'; //背景图
        $image = [
            'url'=>$url['data'],     //二维码
//            'url'=>'3.jpeg',     //二维码
            'stream'=>0,
            'left'=>91,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>186,
            'height'=>186,
            'opacity'=>100,
        ];

        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度

        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));

        //处理了图片
        if(!empty($image)){
            $val = $image;
            $info = getimagesize($val['url']);
            $function = 'imagecreatefrom'.image_type_to_extension($info[2], false);
            if($val['stream']){   //如果传的是字符串图像流
                $info = getimagesizefromstring($val['url']);
                $function = 'imagecreatefromstring';
            }
            $res = $function($val['url']);
            $resWidth = $info[0];
            $resHeight = $info[1];
            //建立画板 ，缩放图片至指定尺寸
            $canvas=imagecreatetruecolor($val['width'], $val['height']);

            imagefill($canvas, 0, 0, $color);
            //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
            imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
            $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
            $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
            //放置图像


            imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度

        }

        //保存到本地
        $filename = 'uploads/poster/qxm'.time().rand(1111,9999).'.jpeg';//拼接文件名称
//        $filename = 'uploads/test6.jpeg';
        $res = imagejpeg($imageRes,$filename);
        imagedestroy($imageRes);
        if(!$res) return false;
        $url = (new OssApi())->upload($filename,$filename);
        dump($url['info']['url']);
        return $url['info']['url'];

    }



}
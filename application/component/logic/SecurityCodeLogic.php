<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/24
 * Time: 14:34
 */

namespace app\component\logic;

use app\component\model\ProductModel;
use app\component\model\ProjectModel;
use app\component\model\SecurityCodeModel;
use app\component\model\UserModel;
use by\infrastructure\helper\CallResultHelper;
use think\Db;

class SecurityCodeLogic   extends BaseLogic
{

    /**
     * 创建验证码
     * @param string $clientId 应用ID
     * @param string $accepter 接收人
     * @param string $type 类型
     * @param int $codeCreateWay
     * @param int $codeLength 验证码长度 3 < length < 8, 默认 6 位
     * @param int $expireTime 过期时间，默认1800秒
     * @return \by\infrastructure\base\CallResult
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create( $accepter,   $codeLength = 6, $expireTime = 60)
    {

        if ($codeLength > 8 || $codeLength < 3) {
            $codeLength = 6;
        }

//        $map = array(
//            'accepter' => $accepter,
//        );
        $now = time();

        // 生成验证码 6 位
        $code =$this->randStr($codeLength);

        // 纪录到数据库

        $data = [
            'code'=> $code,
            'accepter'=> $accepter,
            'create_time'=> $now,
            'expired_time' => ($now + $expireTime),
            'status'  => 0
        ];

        //2. 插入到数据库
        $model =  new SecurityCodeModel();

        $result = $model->save($data);

        return $this->ajaxReturn('发送成功',$code,1);

    }


    /**
     * 生成随机字符
     * @param string $type 该帮助类 ALPHABET|ALPHABET_AND_NUMBERS|NUMBERS
     * @param int $length
     * @return int|string
     */
    public static function randStr( $length = 6)
    {
        return self::randNumbers($length);
    }
    /**
     * 支持随机生成只包含数字的随机字符串长度为1-8
     * @param int $length
     * @return int
     */
    public static function randNumbers($length = 6)
    {
        if ($length < 0) $length = 1;
        if ($length > 8) $length = 8;
        $start = pow(10, $length - 1);
        return mt_rand($start, ($start * 10) - 1);
    }


    public function check($mobile,$code)
    {
        $now = time();
        $where = [
            'accepter'=>  $mobile,
            'code'=>  $code
        ];
        $order = 'expired_time desc';
        $result = Db::table('security_code')->where($where)->order($order)->find();
        if($result){
            if($now > $result['expired_time'])
            {
                return ['status'=>1];
            }else{

                $checkUser =  Db::table('security_code')->where($where)->find();
                return $checkUser;
            }
        }else{
            return ['status'=>2];
        }
    }


//    public function apply($id,$name,$sex,$mobile,$code,$value)
//    {
//
//
//        $now = time();
//        $data = [
//            'username'=> $name,
//            'image_id'=> 1,
//            'mobile'=> $mobile,
//            'sex'=> $sex,
//            'type'=>1,
//            'house_value'=>$value,
//            'create_time'=>$now,
//            'update_time'=>$now
//        ];
//
//        $mobiles = "+86_$mobile";
//
//        $securityCode = Db::table('security_code')->where('accepter',$mobiles)->order('create_time desc')->find();
//
//        if($now > $securityCode['expired_time'])
//        {
//            return $this->ajaxReturn('验证码已过期','',0);
//        }
//
//        if($code != $securityCode['code'])
//        {
//            return $this->ajaxReturn('验证码错误','',-1);
//        }
//
//        $userCode = Db::table('user')->where('mobile',$mobile)->find();
//
//        if($userCode)
//        {
//            $uid = $userCode['id'];
////            $model = new UserModel();
////            $model->save($data,['id'=>$uid]);
//        }
//        else{
//            //向用户表默认注册
//            $uid = Db::name('user')->insertGetId($data);
//        }
//
//        //向项目表默认提交
//        $numbers = "00$uid";
//        $datas = [
//             'uid'=>$uid,
////             'invest_id'=>'',
//            'name' =>$name,
//            'phone'=>$mobile,
//            'sex' =>$sex,
////            'title' =>'',
//            'number' =>$numbers,
////            'type' =>'',
////            'house_address' =>'',
////            'contact_address' =>'',
//            'start_time' =>$now,
//            'end_time' =>'',
////            'amount' =>'',
//            'introduce' =>'',
//            'repay_num' =>'',
////            'return_num' =>'',
////            'actual_money' =>'',
//            'status' =>1,
//            'is_top' =>0,
//            'create_time'=>$now,
//            'update_time'=>$now
//
//        ];
//        $project_id = Db::name('project')->insertGetId($datas);
//
//        return $this->ajaxReturn('申请成功',['uid'=>$uid,'project_id'=>$project_id,'house_value'=>$value],1);
//    }


    public function apply($id ,$name,$sex,$mobile,$code,$value)
    {
        $now = time();
        $data = [
            'username'=> $name,
            'image_id'=> 1,
            'mobile'=> $mobile,
            'sex'=> $sex,
            'type'=>1,
            'house_value'=>$value,
            'create_time'=>$now,
            'update_time'=>$now
        ];

        $mobiles = "+86_$mobile";

        $securityCode = Db::table('security_code')->where('accepter',$mobiles)->order('create_time desc')->find();

        if($now > $securityCode['expired_time'])
        {
            return $this->ajaxReturn('验证码已过期','',0);
        }

        if($code != $securityCode['code'])
        {
            return $this->ajaxReturn('验证码错误','',-1);
        }

        $userCode = Db::table('user')->where('mobile',$mobile)->find();
        if(empty($id)){  //没有登录的情况
            if($userCode){
                $uid = $userCode['id'];
            }else{
                $uid = Db::name('user')->insertGetId($data);
            }
        }else{ //登陆的情况
            $uid = $id;
        }

        //判断该用户是否有申请中的项目
        $res = Db::table('project')->where(['uid'=>$uid,'status'=>array('in',[1,2,3])])->find();
        if($res){
            return $this->ajaxReturn('你的申请正在融资中，请等待','','-1');
        }
        //向项目表默认提交
        $datas = [
            'uid'=>$uid,
//             'invest_id'=>'',
            'name' =>$name,
            'phone'=>$mobile,
            'sex' =>$sex,
//            'title' =>'',
            'number' =>00,
//            'type' =>'',
//            'house_address' =>'',
//            'contact_address' =>'',
            'start_time' =>$now,
            'end_time' =>'',
//            'amount' =>'',
            'introduce' =>'',
            'repay_num' =>'',
//            'return_num' =>'',
//            'actual_money' =>'',
            'status' =>0,
            'is_top' =>0,
            'create_time'=>$now,
            'update_time'=>$now

        ];
        $project_id = Db::name('project')->insertGetId($datas);
        $numbers = '00'.$project_id;
        Db::name('project')->where('id',$project_id)->update(['number'=>$numbers]);
        return $this->ajaxReturn('申请成功',['uid'=>$uid,'project_id'=>$project_id,'house_value'=>$value],1);
    }



    public static function sendSms($config, $url)
    {
        $content = self::juheCurl($url, $config, 1); //请求发送短信

        if ($content) {
            $result = json_decode($content, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //状态为0，说明短信发送成功
                return '短信发送成功';
            } else {
                //状态非0，说明失败
                $msg = $result['reason'];
                return ['msg'=>'短信发送失败','reason'=>$msg];
            }
        } else {
            //返回内容异常，以下可根据业务逻辑自行修改
            return '短信发送失败';
        }
    }

    /**
     * 请求接口返回内容
     * @param  string $url [请求的URL地址]
     * @param bool $params [请求的参数]
     * @param int $isPost
     * @return  string
     */
    public static function juheCurl($url, $params = false, $isPost = 0)
    {
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        $response = curl_exec($ch);
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }


}

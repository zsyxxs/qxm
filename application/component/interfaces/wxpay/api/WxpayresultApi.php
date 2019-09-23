<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/21
 * Time: 19:31
 */

namespace app\component\interfaces\wxpay\api;


use app\admin\controller\Cash;
use app\api\controller\AliMsgApi;
use app\api\controller\UserApi;
use app\component\logic\CommissionLogic;
use app\component\logic\DetailesLogic;
use app\component\logic\IntegralConsumeLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\NotifyLogic;
use app\component\logic\OrderLogic;
use app\component\logic\QxCommissionLogic;
use app\component\logic\RoleLogic;
use app\component\logic\UserLogic;
use app\component\model\CashTransfersModel;
use app\component\model\GoodsModel;
use app\component\model\ManagersModel;
use app\component\model\OrderPayModel;
use app\component\model\OrdersModel;
use app\component\model\PayLogModel;
use app\component\model\PrepayModel;
use app\component\model\RefundModel;
use app\component\model\SpellGroupModel;
use think\Db;

class WxpayresultApi extends WxpayUrl
{

    //支付成功回调
    public function notify_results()
    {
        //接受回调过来的数据
        $xmlData = file_get_contents('php://input');
        $data = (new WxpayvalueApi())->XMLDataParse($xmlData);
        (new NotifyLogic())->save(['content' => json_encode($data)]);
        if($data['result_code'] == 'SUCCESS'){
            Db::startTrans();
            try{
                //支付之前会生成一个 订单，回调会接受这个订单id，根据订单id，回去对应的支付用户id'
//                $order_num = 'qxm2019082255073014269395';
                //订单号
                $order_num = isset($data['out_trade_no']) && !empty($data['out_trade_no']) ? $data['out_trade_no'] : 0;
                //如果订单存在，且未支付才处理
                $orderInfo = (new OrderLogic())->getInfo(['order_num' => $order_num]);
                if($orderInfo && $orderInfo['status'] == 0){
                    //更新订单的支付状态为支付成功
                    (new OrderLogic())->save(['status' => 1],['id'=>$orderInfo['id']]);
//                  $orderInfo = (new OrderLogic())->getInfo(['id' => $order_id]);
                    $uid = $orderInfo['uid'];
                    //获取当前用户的信息
                    $userInfo = (new UserLogic())->getInfo(['id' => $uid]);



                    //判断当前付费目的
                    if($userInfo['level'] == 0){
                        //普通会员升级为一级会员
                        //根据填写的邀请码获取上级id
                        $parentInfo = (new UserLogic())->getInfo(['code' => $userInfo['invite_code']],false,'id,p_id,level,level_one,level_two');

                        //更新用户信息
                        //用户升级到一级,绑定上级id
                        $level = $userInfo['level'] + 1;
                        (new UserLogic())->save(['level' => $level,'p_id'=>$parentInfo['id']],['id' => $uid]);



                        //判断该用户上级等级以及下线人数
//                        $parentInfo = (new UserLogic())->getInfo(['id'=>$userInfo['p_id']],false,'id,level,level_one,level_two');
                        $level = '';
                        if($parentInfo['level_one'] == 2 && $parentInfo['level'] < 3){
                            $level = 3;
                        }elseif ($parentInfo['level_one'] == 14 && $parentInfo['level'] < 5){
                            $level = 5;
                        }elseif ($parentInfo['level_one'] == 149 && $parentInfo['level'] < 6){
                            $level = 6;
                        }
                        $level_one = $parentInfo['level_one'] + 1;
                        if(!empty($level)){
                            //更新上级用户信息
                            (new UserLogic())->save(['level' => $level,'level_one'=>$level_one],['id'=>$parentInfo['id']]);
                        }else{
                            (new UserLogic())->save(['level_one'=>$level_one],['id'=>$parentInfo['id']]);
                        }



                        //判断上级的上级的等级以及下线人数，以及是否需要发放二级佣金
                        $grandInfo = (new UserLogic())->getInfo(['id'=>$parentInfo['p_id']],false,'id,p_id,level,level_one,level_two');
                        //判断该当前用户二级下线是否达到升级标准
                        $grand_level = '';
                        if($grandInfo['level_two'] == 2 && $grandInfo['level'] < 4){
                            $grand_level = 4;
                        }
                        $level_two = $grandInfo['level_two'] + 1;
                        //增加用户的二级下线人数
                        if(!empty($grand_level)){
                            (new UserLogic())->save(['level' => $grand_level,'level_two' => $level_two],['id'=>$grandInfo['id']]);
                        }else{
                            (new UserLogic())->save(['level_two' => $level_two],['id'=>$grandInfo['id']]);
                        }



                        //判断要不要给该用户发放二级佣金
                        if($grandInfo['level_two'] < 2){
                            //为上级的上级生成一条待发放二级佣金记录
                            (new QxCommissionLogic())->save([
                                'uid' => $grandInfo['id'],
                                'money' => LEVEL_TWO_MONEY,
                                'q_uid' => $uid,
                                'status' => 0
                            ]);
                        }


//                        //二级下线数量达到发放之前的待发放佣金
                        if($grandInfo['level_two'] == 2){
                            //生成一个待发放的佣金记录
                            (new QxCommissionLogic())->save([
                                'uid' => $grandInfo['id'],
                                'money' => LEVEL_TWO_MONEY,
                                'q_uid' => $uid,
                                'status' => 0
                            ]);

                            //发放之前的待发放的二级佣金
                            //获取该用户之前待发放的佣金记录
                            $list = (new QxCommissionLogic())->getLists(['uid' => $grandInfo['id'],'status'=>0]);
                            if(!empty($list)){
                                foreach ($list as $v){
                                    //将记录更改为已发放，将佣金加入到用户余额
                                    (new QxCommissionLogic())->save(['status'=>1 ],['id'=>$v['id']]);
                                    (new UserLogic())->setInc(['id'=>$v['uid']],'money',$v['money']);
                                }
                            }
                        }


                        //二级下线人数符合，直接发放二级下线佣金
                        if($grandInfo['level_two'] > 2){
                            (new UserLogic())->setInc(['id'=>$grandInfo['id']],'money',LEVEL_TWO_MONEY);

                            //为上级的上级添加一条牵线佣金获得记录
                            (new QxCommissionLogic())->save([
                                'uid' => $grandInfo['id'],
                                'money' => LEVEL_TWO_MONEY,
                                'q_uid' => $uid
                            ]);
                        }

                    }elseif ($userInfo['level'] >=2 && $userInfo['level'] <= 4){
                        //用户通过付费直接升级到五级
                        //更新付费用户信息
                        $level = 5;
                        (new UserLogic())->save(['level' => $level],['id' => $uid]);

                    }elseif ($userInfo['level'] == 5){
                        //每次用户付费在原有基础上升级一级
                        $level = $userInfo['level'] + 1;
                        (new UserLogic())->save(['level' => $level],['id' => $uid]);

                    }

                }

                Db::commit();
                echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';exit();


            }catch (\Exception $e){
                Db::rollback();
            }
        }else{
            exit('fail');
        }





    }

    //微信统一下单查询接口
    public  function order_query($order_num){
        $post['appid'] = $this->appId;  //公众号id
        $post['mch_id'] = $this->mchId;  //商户id
        $post['nonce_str'] = (new WxpayvalueApi())->randomkeys(32); //随机字符串
        $post['out_trade_no'] = $order_num;
        $post['sign'] = (new WxpayvalueApi)->MakeSign($post, $this->key);//签名
        $post_xml = (new WxpayvalueApi)->arrayToXml($post);
        $xml = (new WxpayvalueApi)->http_request($this->queryUrl, $post_xml);     //POST方式请求http
        $data = (new WxpayvalueApi)->XMLDataParse($xml);               //将【统一下单】api返回xml数据转换成数组，全要大写

        if($data['result_code'] == 'SUCCESS' && $data['return_msg'] == 'OK'){
            Db::startTrans();
            try{
                //订单号
                $order_num = isset($data['out_trade_no']) && !empty($data['out_trade_no']) ? $data['out_trade_no'] : 0;
                //如果订单存在，且状态为1，直接返回
                $orderInfo = (new OrderLogic())->getInfo(['order_num' => $order_num]);
                if($orderInfo && $orderInfo['status']==1){
                    return 'OK';
                }
                //如果订单存在，且状态为0
                if($orderInfo && $orderInfo['status'] == 0){
                    //更新订单的支付状态为支付成功
                    (new OrderLogic())->save(['status' => 1],['id'=>$orderInfo['id']]);
//                  $orderInfo = (new OrderLogic())->getInfo(['id' => $order_id]);
                    $uid = $orderInfo['uid'];
                    //获取当前用户的信息
                    $userInfo = (new UserLogic())->getInfo(['id' => $uid]);



                    //判断当前付费目的
                    if($userInfo['level'] == 0){
                        //普通会员升级为一级会员
                        //根据填写的邀请码获取上级id
                        $parentInfo = (new UserLogic())->getInfo(['code' => $userInfo['invite_code']],false,'id,p_id,level,level_one,level_two');

                        //更新用户信息
                        //用户升级到一级,绑定上级id
                        $level = $userInfo['level'] + 1;
                        (new UserLogic())->save(['level' => $level,'p_id'=>$parentInfo['id']],['id' => $uid]);



                        //判断该用户上级等级以及下线人数
//                        $parentInfo = (new UserLogic())->getInfo(['id'=>$userInfo['p_id']],false,'id,level,level_one,level_two');
                        $level = '';
                        if($parentInfo['level_one'] == 2 && $parentInfo['level'] < 3){
                            $level = 3;
                        }elseif ($parentInfo['level_one'] == 14 && $parentInfo['level'] < 5){
                            $level = 5;
                        }elseif ($parentInfo['level_one'] == 149 && $parentInfo['level'] < 6){
                            $level = 6;
                        }
                        $level_one = $parentInfo['level_one'] + 1;
                        if(!empty($level)){
                            //更新上级用户信息
                            (new UserLogic())->save(['level' => $level,'level_one'=>$level_one],['id'=>$parentInfo['id']]);
                        }else{
                            (new UserLogic())->save(['level_one'=>$level_one],['id'=>$parentInfo['id']]);
                        }



                        //判断上级的上级的等级以及下线人数，以及是否需要发放二级佣金
                        $grandInfo = (new UserLogic())->getInfo(['id'=>$parentInfo['p_id']],false,'id,p_id,level,level_one,level_two');
                        //判断该当前用户二级下线是否达到升级标准
                        $grand_level = '';
                        if($grandInfo['level_two'] == 2 && $grandInfo['level'] < 4){
                            $grand_level = 4;
                        }
                        $level_two = $grandInfo['level_two'] + 1;
                        //增加用户的二级下线人数
                        if(!empty($grand_level)){
                            (new UserLogic())->save(['level' => $grand_level,'level_two' => $level_two],['id'=>$grandInfo['id']]);
                        }else{
                            (new UserLogic())->save(['level_two' => $level_two],['id'=>$grandInfo['id']]);
                        }



                        //判断要不要给该用户发放二级佣金
                        if($grandInfo['level_two'] < 2){
                            //为上级的上级生成一条待发放二级佣金记录
                            (new QxCommissionLogic())->save([
                                'uid' => $grandInfo['id'],
                                'money' => LEVEL_TWO_MONEY,
                                'q_uid' => $uid,
                                'status' => 0
                            ]);
                        }


//                        //二级下线数量达到发放之前的待发放佣金
                        if($grandInfo['level_two'] == 2){
                            //生成一个待发放的佣金记录
                            (new QxCommissionLogic())->save([
                                'uid' => $grandInfo['id'],
                                'money' => LEVEL_TWO_MONEY,
                                'q_uid' => $uid,
                                'status' => 0
                            ]);

                            //发放之前的待发放的二级佣金
                            //获取该用户之前待发放的佣金记录
                            $list = (new QxCommissionLogic())->getLists(['uid' => $grandInfo['id'],'status'=>0]);
                            if(!empty($list)){
                                foreach ($list as $v){
                                    //将记录更改为已发放，将佣金加入到用户余额
                                    (new QxCommissionLogic())->save(['status'=>1 ],['id'=>$v['id']]);
                                    (new UserLogic())->setInc(['id'=>$v['uid']],'money',$v['money']);
                                }
                            }
                        }


                        //二级下线人数符合，直接发放二级下线佣金
                        if($grandInfo['level_two'] > 2){
                            (new UserLogic())->setInc(['id'=>$grandInfo['id']],'money',LEVEL_TWO_MONEY);

                            //为上级的上级添加一条牵线佣金获得记录
                            (new QxCommissionLogic())->save([
                                'uid' => $grandInfo['id'],
                                'money' => LEVEL_TWO_MONEY,
                                'q_uid' => $uid
                            ]);
                        }

                    }elseif ($userInfo['level'] >=2 && $userInfo['level'] <= 4){
                        //用户通过付费直接升级到五级
                        //更新付费用户信息
                        $level = 5;
                        (new UserLogic())->save(['level' => $level],['id' => $uid]);

                    }elseif ($userInfo['level'] == 5){
                        //每次用户付费在原有基础上升级一级
                        $level = $userInfo['level'] + 1;
                        (new UserLogic())->save(['level' => $level],['id' => $uid]);

                    }

                }

                Db::commit();
                return 'OK';
            }catch (\Exception $e){
                Db::rollback();
            }
        }else{
            return 'fail';
        }
    }


    //微信统一下单支付接口
    public function Pay($total_fee,$openid,$order_id,$bodys){
        if(empty($total_fee)){
            echo json_encode(array('state'=>0,'Msg'=>'金额有误'));exit;
        }
        if(empty($openid)){
            echo json_encode(array('state'=>0,'Msg'=>'登录失效，请重新登录(openid参数有误)'));exit;
        }
        if(empty($order_id)){
            echo json_encode(array('state'=>0,'Msg'=>'自定义订单有误'));exit;
        }
        $nonce_strs = (new WxpayvalueApi())->randomkeys(32);
        $appid = $this->appId;
        $mch_id = $this->mchId;
        $body = $bodys;
        $key = $this->key; //key为商户平台设置的密钥key
        $nonce_str =    $nonce_strs;//随机字符串
        $notify_url =   $this->apiUrl.'/weixin_api/notify_url';  //支付完成回调地址url,不能带参数
//        $notify_url =   'http://api.mingpian.8raw.com/weixin_api/notify_url';  //支付完成回调地址url,不能带参数
        $out_trade_no = $order_id;//商户订单号
        $spbill_create_ip = $_SERVER['SERVER_ADDR'];
//        $trade_type = 'MWEB';//交易类型 默认MWEB
        $trade_type = 'JSAPI';//交易类型 默认MWEB

        //这里是按照顺序的 因为下面的签名是按照(字典序)顺序 排序错误 肯定出错
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;//随机字符串
        $post['notify_url'] = $notify_url;
        $post['openid'] = $openid;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = $spbill_create_ip;//服务器终端的ip
        $post['total_fee'] = intval($total_fee);        //总金额 最低为一分钱 必须是整数
        $post['trade_type'] = $trade_type;

        $sign = (new WxpayvalueApi)->MakeSign($post,$key);//签名
//        $this->sign = $sign;
        $post['sign'] = $sign;

        //把数组转化成xml格式
        $post_xml = (new WxpayvalueApi)->arrayToXml($post);

        //统一下单接口prepay_id
        $url = $this->unifiedorderUrl;
        $xml = (new WxpayvalueApi)->http_request($url,$post_xml);     //POST方式请求http
        $array = (new WxpayvalueApi)->XMLDataParse($xml);               //将【统一下单】api返回xml数据转换成数组，全要大写

        //对返回结果进行判断
        if($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS'){
            //二次签名所需的随机字符串
            $post["nonce_str"] = (new WxpayvalueApi())->randomkeys(32);
            //二次签名所需的时间戳
            $post['timeStamp'] = time()."";
            //二次签名剩余参数的补充
            $secondSignArray = array(
                "appid"=>$post['appid'],
                "noncestr"=>$post['nonce_str'],
                "package"=>"prepay_id=".$array['prepay_id'],
//                "prepayid"=>$array['prepay_id'],
                'signtype' => "MD5",
                "timestamp"=>$post['timeStamp']
            );
            (new PrepayModel())->save($secondSignArray);
            $secondSignArray = array(
                "appId"=>$post['appid'],
                "nonceStr"=>$post['nonce_str'],
                "package"=>"prepay_id=".$array['prepay_id'],
//                "prepayid"=>$array['prepay_id'],
                'signType' => "MD5",
                "timeStamp"=>$post['timeStamp']
            );
            $json['success'] = 1;
            $json['ordersn'] = $post["out_trade_no"]; //订单号
            $json['key'] = $key;
            $json['order_arr'] = $secondSignArray;  //返给前台APP的预支付订单信息
            $newSign = (new WxpayvalueApi)->MakeSign($secondSignArray,$key);  //预支付订单签名
            $json['order_arr']['sign'] = $newSign;
            $json['data'] = "预支付完成";

            //保存该订单的预支付订单
            $secondSignArray['key'] = $key;
            $secondSignArray['sign'] = $newSign;
            $secondSignArray['order_num'] = $post["out_trade_no"];



            return $json;

        }else{
            $json['success'] = 0;
            $json['text'] = "错误";
            $json['return_code'] = $array['return_code'];
            $json['return_msg'] = $array['return_msg'];
            echo json_encode($json);
        }

    }



    //微信企业向个人转账
    public function transfers($uid,$cash_id,$open_id,$username,$description,$money)
    {
        $mch_appid = $this->appId;                                          //公众账号appid
        $mchid = $this->mchId;                                              //商户号
        $nonce_str= (new WxpayvalueApi())->randomkeys(32);                 //随机数
        $partner_trade_no = 'qxm'.time().rand(10000000, 99999999);       //商户订单号
        $openid = $open_id;                                                 //用户唯一标识,上一步授权中获取
        $check_name= 'NO_CHECK';                                            //校验用户姓名选项，NO_CHECK：不校验真实姓名， FORCE_CHECK：强校验真                                                                            实姓名（未实名认证的用户会校验失败，无法转账），OPTION_CHECK：针对已                                                                             实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
        $re_user_name= $username;                                             //用户姓名
        $amount = $money;                                                      //企业金额，这里是以分为单位（必须大于100分）
        $desc = $description;                                              //描述
        $spbill_create_ip = '112.124.200.27';                                 //请求ip

        $dataArr=array();
        $dataArr['amount'] = $amount;
        $dataArr['check_name'] = $check_name;
        $dataArr['desc'] = $desc;
        $dataArr['mch_appid'] = $mch_appid;
        $dataArr['mchid'] = $mchid;
        $dataArr['nonce_str'] = $nonce_str;
        $dataArr['openid'] = $openid;
        $dataArr['partner_trade_no'] = $partner_trade_no;
        $dataArr['re_user_name'] = $re_user_name;
        $dataArr['spbill_create_ip'] = $spbill_create_ip;
        $key = $this->key;
        $sign = (new WxpayvalueApi)->MakeSign($dataArr,$key);//签名
        $dataArr['sign'] = $sign;
        //把数组转化成xml格式
        $post_xml = (new WxpayvalueApi)->arrayToXml($dataArr);
        //企业向用户付款接口
        $url = $this->transfersUrl;
        $xml = (new WxpayvalueApi)->http_request_certificate($url,$post_xml);     //POST方式请求http,变添加证书
        $array = (new WxpayvalueApi)->XMLDataParse($xml);               //将【企业向用户付款接口】api返回xml数据转换成数组，全要大写

        if($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS'){
            //付款成功，记录到数据表中，并返回数据
            $data = [
                'uid' => $uid,
                'cash_id' => $cash_id,
                'partner_trade_no' => $array['partner_trade_no'],
                'payment_no' => $array['payment_no']
            ];
            $res = (new CashTransfersModel())->save($data);

        }
        return $array;
    }

    public function cardTransfers($g_uid,$uc_id,$open_id,$username,$description,$money)
    {
        $mch_appid = $this->appId;                                          //公众账号appid
        $mchid = $this->mchId;                                              //商户号
        $nonce_str= (new WxAuthvalueApi())->randomkeys(32);                 //随机数
        $partner_trade_no = 'jinlie'.time().rand(10000000, 99999999);       //商户订单号
        $openid = $open_id;                                                 //用户唯一标识,上一步授权中获取
        $check_name= 'NO_CHECK';                                            //校验用户姓名选项，NO_CHECK：不校验真实姓名， FORCE_CHECK：强校验真                                                                            实姓名（未实名认证的用户会校验失败，无法转账），OPTION_CHECK：针对已                                                                             实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
        $re_user_name= $username;                                             //用户姓名
        $amount = $money;                                                      //企业金额，这里是以分为单位（必须大于100分）
        $desc = $description;                                              //描述
        $spbill_create_ip = '47.95.219.88';                                 //请求ip

        $dataArr=array();
        $dataArr['amount'] = $amount;
        $dataArr['check_name'] = $check_name;
        $dataArr['desc'] = $desc;
        $dataArr['mch_appid'] = $mch_appid;
        $dataArr['mchid'] = $mchid;
        $dataArr['nonce_str'] = $nonce_str;
        $dataArr['openid'] = $openid;
        $dataArr['partner_trade_no'] = $partner_trade_no;
        $dataArr['re_user_name'] = $re_user_name;
        $dataArr['spbill_create_ip'] = $spbill_create_ip;
        $key = $this->key;
        $sign = (new WxAuthvalueApi)->MakeSign($dataArr,$key);//签名
        $dataArr['sign'] = $sign;
        //把数组转化成xml格式
        $post_xml = (new WxAuthvalueApi)->arrayToXml($dataArr);
        //企业向用户付款接口
        $url = $this->transfersUrl;
        $xml = (new WxAuthvalueApi)->http_request_certificate($url,$post_xml);     //POST方式请求http,变添加证书
        $array = (new WxAuthvalueApi)->XMLDataParse($xml);               //将【企业向用户付款接口】api返回xml数据转换成数组，全要大写
        //添加回调日志
        $res = (new PayLogModel())->data(['text'=>json_encode($array,JSON_UNESCAPED_UNICODE),'order_id'=>$uc_id,'sn'=>$partner_trade_no])->save();

        return $array;
    }

    //退款
    public function refund($uid,$weixin_id,$order_num,$order_total_fee)
    {
        $mch_appid = $this->appId;                                          //公众账号appid
        $mchid = $this->mchId;                                              //商户号
        $nonce_str= (new WxAuthvalueApi())->randomkeys(32);                 //随机数

        $dataArr=array();
        $dataArr['appid'] = $mch_appid;
        $dataArr['mch_id'] = $mchid;
        $dataArr['nonce_str'] = $nonce_str;
        $dataArr['transaction_id'] = $weixin_id;            //微信订单号
        $dataArr['out_trade_no'] = $order_num;              //商户订单号
        $dataArr['out_refund_no'] = $order_num;             //商户退款单号
        $dataArr['total_fee'] = $order_total_fee;           //订单金额
        $dataArr['refund_fee'] = $order_total_fee;          //退款金额
        $key = $this->key;
        $sign = (new WxAuthvalueApi)->MakeSign($dataArr,$key);//签名
        $dataArr['sign'] = $sign;
        //把数组转化成xml格式
        $post_xml = (new WxAuthvalueApi)->arrayToXml($dataArr);
        //企业向用户付款接口
        $url = $this->refundUrl;
        $xml = (new WxAuthvalueApi)->http_request_certificate($url,$post_xml);     //POST方式请求http,变添加证书
        $array = (new WxAuthvalueApi)->XMLDataParse($xml);               //将【企业向用户付款接口】api返回xml数据转换成数组，全要大写

        if($array['return_code'] == 'SUCCESS' && $array['result_code'] == 'SUCCESS'){
            //退款成功，记录到数据表中，并返回数据
            $data = [
                'uid' => $uid,
                'sn' => $array['out_refund_no'],
                'refund_id' => $array['refund_id'],
                'refund_fee' => $array['refund_fee']
            ];
            $res = (new RefundModel())->save($data);
        }
        return $array;



    }




}

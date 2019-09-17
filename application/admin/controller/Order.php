<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/19
 * Time: 15:26
 */

namespace app\admin\controller;



use app\api\controller\WeixinApi;
use app\component\interfaces\weixin\api\WxresultApi;
use app\component\logic\OrderLogic;
use app\component\logic\UserLogic;
use think\Db;
use think\Request;

class Order extends BaseAdmin
{
    public function index()
    {
        $start = $this->_param('start','');
        $end = $this->_param('end','');
        $this->assign('start',$start);
        $this->assign('end',$end);
        $type = $this->_param('type','');
        $pagesize = 20;
        if($type !== ''){
            $where = ['c.status'=>$type];//默认显示全部提现列表
        }else{
            $where = [];
        }
        $username = $this->_param('username');
        if($username){
            $where = $where ? array_merge($where, ['u.username'=>$username]) : ['u.username'=>$username];
        }
        $order_num = $this->_param('order_num');
        if($order_num){
            $where = $where ? array_merge($where, ['c.order_num'=>$order_num]) : ['c.order_num'=>$order_num];
        }
        $order= 'c.create_time DESC';
        $list = (new OrderLogic())->getListss($where,$pagesize,$order,$start,$end);
        $count = $list['count'];
        $this->assign('list',$list['list']);
        $this->assign('count',$count);
        $this->assign('page',$list['page']);
        $this->assign('type',$type);

        $pCode = cookie('pCode');
        $this->assign('pCode', $pCode ? $pCode : 0);
        return view();
    }

    public function chCode(Request $request)
    {
        if ($request->isAjax() && $request->isPost()) {
            $code = input("param.code");
            $pCode = cookie("pCode");
            if($code==$pCode){
                cookie("pCode", 1, 1800);
                return json(['status'=>1]);
            }else{
                return json(['status' =>0, 'info'=>'验证码有误']);
            }
        }
    }

    public function sendPhone(Request $request)
    {
        if ($request->isAjax() && $request->isPost()) {
            $phone = 18510249708;
            $info = cookie("sendPhone");
            if($info){
                //60秒内发送过，则不能再发
                return json(['status'=>0, 'info'=>'短信发送太频繁']);
            }else{
                //没有发送过短信
                $code = rand(1111,9999);
                $result = (new SmsDemo())->sendSms($phone,$code);
                cookie("sendPhone", 1, 60);
                if($result->Code !== 'OK'){
                    return json(['status'=>0, 'info'=>'短信发送失败']);
                }
                cookie("pCode", $code, 1800);
                return json(['status'=>1, 'info'=>'短信发送成功']);
            }
        }
    }

    public function setStatus(Request $request)
    {
        if($request->isAjax()){

            $data = input();
            $cashInfo = (new CashLogic())->getInfo(['id'=>$data['id']]);
            if($cashInfo['status'] == 1){
                return ['status' => '请勿重复提现'];
            }
            //将提现记录id保存
            $res = (new CashLogLogic())->add($data['id']);
            if($res == '1001'){
                return ['status' => '请勿重复提现'];
            }
            if($res){
                //统计该提现记录id数量
                $count = (new CashLogLogic())->count(['c_id' =>$data['id']]);
                if($count > 1){
                    (new CashLogLogic)->del($data['id']);
                    return ['status' => '请勿重复提现'];
                }
                //获取用户余额
                $user = (new UserLogic())->getInfo(['id' => $cashInfo['uid']]);
                if($user['status'] !== 1){
                    (new CashLogLogic)->del($data['id']);
                    return ['status' => '账号已被封'];
                }
                $cashing = $cashInfo['money'];
                if($user['money'] < $cashing ){
                    (new CashLogLogic)->del($data['id']);
                    return ['status' => '余额不足'];
                }
//                $result = $this->cash_transfer($data['id'],$cashInfo);
                $result = (new WeixinApi())->transfers($data['id'],$cashInfo['uid'],$cashInfo['money']);

                if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
                    (new CashLogic())->setStatus($data['status'],$data['id']);
                    return ['status' =>'提现成功'];
                }elseif ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'FAIL'){
                    (new CashLogLogic)->del($data['id']);
                    return ['status' => $result['err_code_des']];
                }else{
                    (new CashLogLogic)->del($data['id']);
                    return ['status' => '提现失败'];
                }
            }


        }
    }






}
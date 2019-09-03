<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/12
 * Time: 10:03
 */

namespace app\api\helper;


use app\api\controller\BankLists;
use app\api\controller\WeixinApi;
use app\component\logic\BankCardLogic;
use app\component\logic\BankLogic;
use app\component\logic\CashLogic;
use app\component\logic\GoodsCommissionLogic;
use app\component\logic\UserBuyLogic;
use app\component\logic\UserCommissionLogic;
use app\component\logic\UserLogic;
use app\component\logic\WithdrawalLogic;
use app\component\model\BankCardModel;
use think\Db;


class WalletApiHelper
{
    public function allowBanks($pageNo,$pageSize)
    {
        $res = (new BankLogic())->allowBanks($pageNo,$pageSize);
        return $res;
    }

    public function addBankCard($uid,$name,$person_id,$card_number,$phone)
    {
        //验证该银行卡号是否已经添加过
        $param = ['uid'=>$uid,'card_number'=>$card_number,'status'=>1];
        $userBank = (new BankCardLogic())->checkInfo($param);
        if(!empty($userBank)){
            //该用户已经添加过该银行卡
            return '1003';
        }
        //获取对应的银行卡号所属银行的缩写
        $bankInfo = self::getBankInfo($card_number);
        //判断该银行卡是否正确
        if($bankInfo['validated'] == false){
            //银行卡号不正确
            return '1002';
        }

        //验证该银行卡号是否为后台允许添加的银行  通过缩写验证
        $res = (new BankLogic())->checkBank($bankInfo['bank']);
        if(!$res){
            //缩写验证不通过，通过名称验证
            //获得对应银行卡所属银行的名称
            $bankName = (new BankLists())->bank($card_number);
            if(!$bankName){
                //银行卡号不正确
                return '1002';
            }
            $bankName = explode('-',$bankName);
            //通过银行标题验证
            $res = (new BankLogic())->checkTitle($bankName[0]);
            if(!$res){
                return '1001';
            }

            $bank_id = $res['id'];
            $data = [
                'uid' => $uid,
                'name' => $name,
                'person_id' => $person_id,
                'card_number' => $card_number,
                'phone' => $phone,
                'bank_id' => $bank_id
            ];
            $result = (new BankCardLogic())->add($data);
            return $result;

        }else{
            //匹配后台允许添加的银行信息
            $where = ['short'=>$bankInfo['bank']];
            $info = (new BankLogic())->getInfoByWhere($where);
            $bank_id = $info['id'];
            $data = [
                'uid' => $uid,
                'name' => $name,
                'person_id' => $person_id,
                'card_number' => $card_number,
                'phone' => $phone,
                'bank_id' => $bank_id
            ];
            $result = (new BankCardLogic())->add($data);
            return $result;
        }


    }

    public function bankList($uid,$pageNo,$pageSize)
    {
        $res = (new BankCardLogic())->getLists($uid,$pageNo,$pageSize);
        return $res;
    }

    public function bankDel($id)
    {
        $res = (new BankCardModel())->where('id',$id)->delete();
        return $res;
    }

    public function goodsCommission($uid,$pageNo,$pageSize)
    {
        $res = (new GoodsCommissionLogic())->goodsCommission($uid,$pageNo,$pageSize);
        return $res;
    }

    public function cardCommission($uid,$pageNo,$pageSize)
    {
        $res = (new UserCommissionLogic())->cardCommission($uid,$pageNo,$pageSize);
        return $res;
    }

    public function cardInviteCommission($uid,$pageNo,$pageSize)
    {
        $res = (new UserBuyLogic())->cardInviteCommission($uid,$pageNo,$pageSize);
        return $res;
    }

    public function addCash($uid,$money)
    {
        Db::startTrans();
        try{
            //获取提现设置数据
            $withdrawal = (new WithdrawalLogic())->getInfoByType(1);
            $w_balance = $withdrawal['balance'];//起提余额
            $w_money = $withdrawal['money'];//起提金额
            $w_rate = $withdrawal['rate'];//提现费率
            $w_min = $withdrawal['min'];//提现费率
            //判断是否达到可提现余额标准
            $userInfo = (new UserLogic())->getInfoByUid($uid);
            if($userInfo['total_cash'] < $w_balance){
                //余额不足以体现
                return '1001';
            }

            //判断提现金额是否大于100
            if($money < $w_money){
                //每次提现金额不能小于100
                return '1002';
            }

            //提现手续费
            $service_money = ($money * $w_rate) / 100;
            if($service_money < $w_min){
                //手续费为最低提现手续费
                $service_money = $w_min;
            }

            //判断可提现余额是否大于提现余额
            $total_cash = $this->totalCash($uid);
            $allow_money = $total_cash['allow_cash'];
            if($money > $allow_money){
                //每次提现金额不能大于可提现余额
                return '1003';
            }

            $data = [
                'uid' => $uid,
                'money' => $money,
                'service_money' => $service_money
            ];
            $res = (new CashLogic())->addCash($data);
            Db::commit();
            return $res;
        }catch (\ Exception $e){
            Db::rollback();
        }

    }

    public function cashList($uid,$pageNo,$pageSize)
    {
        $res = (new CashLogic())->cashList($uid,$pageNo,$pageSize);
        return $res;
    }

    public function totalCash($uid)
    {
        //总的余额
        $res = (new UserLogic())->getInfoByUid($uid);
        //获取该用户申请的体现总额
        $result = (new CashLogic())->getSumByuid($uid);
        //可提现总金额
        $allow_cash = ($res['total_cash']  - $result *100 ) /100;
        return ['total_cash'=> ($res['total_cash'] / 100),'allow_cash'=> ($allow_cash)];
    }







    public function getBankInfo($number)
    {
        $url = 'https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&';
        $data = [
            'cardNo' => $number,
            'cardBinCheck' => 'true',
        ];
        $result = self::get_result_get($url,$data);
        return json_decode($result,true);
    }

    public function getBankLogo($bank)
    {
        $url = 'https://apimg.alipay.com/combo.png?charset=utf-8&d=cashier&';
        $data = ['t'=>$bank];
        $result = self::get_result_get($url,$data);
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

}
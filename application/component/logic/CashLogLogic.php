<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\CashLogModel;
use app\component\model\CashModel;
use app\component\model\DyBannersModel;
use app\component\model\UserModel;
use think\Db;

class CashLogLogic  extends BaseLogic
{

    public function add($id)
    {
        $res = Db::table('cash_log')->where('c_id',$id)->find();
        if($res){
            return '1001';
        }else{
            $res = (new CashLogModel())->save(['c_id'=>$id]);
            return $res;
        }

    }

//    public function getInfo($id)
//    {
//        $res = Db::table('cash_log')->where('c_id',$id)->find();
//        return $res;
//    }

//    public function count($id)
//    {
//        $num = Db::table('cash_log')->where('c_id',$id)->count();
//        return $num;
//    }

    public function del($id)
    {
        $res = (new CashLogModel())->where('c_id',$id)->delete();
    }

}

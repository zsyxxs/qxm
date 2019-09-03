<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22
 * Time: 10:11
 */

namespace app\api\helper;


use app\component\logic\BannersLogic;
use app\component\logic\CompanyLogic;
use app\component\logic\ContactLogic;
use app\component\logic\HouseLogic;
use app\component\logic\InvestLogic;
use app\component\logic\InviteUserLogic;
use app\component\logic\NewsLogic;
use app\component\logic\ProductLogic;
use app\component\logic\ProjectLogic;
use app\component\logic\QuestionLogic;
use app\component\logic\SecurityCodeLogic;
use app\component\logic\UserLogic;
use think\Db;

class FdApiHelper
{
    protected $pageNo;
    protected $pageSize;
    protected $cacheKey;
    protected $position;

    /**
     * FdApiHelper constructor.
     * @param int $pageNo
     * @param int $pageSize
     */
    public function __construct($pageNo = 1,$pageSize = 20,$position = 1)
    {
        $this->pageNo = $pageNo;
        $this->pageSize = $pageSize;
        $this->position = $position;
        $this->cacheKey = "K_".$pageNo."_".$pageSize."_".$position;
    }

    /**
     * @param $position
     * @param $pagesize
     * @param $pageNo
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getBanners($position,$pagesize,$pageNo)
    {
        $res = (new BannersLogic())->queryBy($position,$pagesize,$pageNo);
        return $res;
    }


    /**
     * @param $order
     * @param $type
     * @param $pageNo
     * @param $pagesize
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getNews($order,$type,$pageNo,$pagesize)
    {
        $res = (new NewsLogic())->queryBy($order,$type,$pageNo,$pagesize);
        return $res;
    }

    /**
     * @param $pageNo
     * @param $pagesize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProduct($pageNo,$pagesize)
    {
        $res = (new ProductLogic())->queryBy($pageNo,$pagesize);
        return $res;
    }

    public function getProductDetail($id)
    {
        $res = (new ProductLogic())->getInfoById($id);
        return $res;
    }

    /**
     * @param $type
     * @param $pageNo
     * @param $pagesize
     * @param $order
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProjects($type,$pageNo,$pagesize,$order)
    {
        if($type == 1){
            //全部
            $where = ['status' => array('in',[2,3,4])];
        }elseif ($type == 2){
            //已签约
            $where = ['status' => 4];
        }else{
            //融资中
            $where = ['status' => array('in',[2,3])];
        }
        if(!empty($order)){
            $where = ['status' => 4];
        }
        $res = (new ProjectLogic())->queryBy($where,$pageNo,$pagesize,$order);
        return $res;

    }

    public function getInvestCount()
    {
        //统计银行资方的总投资金额
        $type = 1;
        $bank_total = (new InvestLogic())->getMoney($type);
        //统计机构资方的总投资金额
        $type = 2;
        $organ_total = (new InvestLogic())->getMoney($type);
        //统计个体资方的总投资金额
        $type = 3;
        $person_total = (new InvestLogic())->getMoney($type);
        //全部总金额
        $total = $person_total + $organ_total + $bank_total;
        //获取总共已经被投资的项目数量
        $num = (new ProjectLogic())->getProjectCount();
        return ['person'=>$person_total,'organ'=>$organ_total,'bank'=>$bank_total,'total'=>$total,'num'=>$num];
    }

    /**
     * 项目详情
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getInfo($id)
    {
        $res = (new ProjectLogic())->queryByProject($id);
        return $res;
    }

    /**
     * 用户头像
     * @param $id
     * @param $image_id
     * @return string
     */
    public function saveUserImage($id, $image_id)
    {
        $res = (new UserLogic())->saveImage($id,$image_id);
        return $res;
    }


    /**
     * 用户密码修改
     * @param $id
     * @param $pwd
     * @param $newpwd
     * @param $npwd
     * @return string
     */
    public function newpwd($id, $pwd, $newpwd, $npwd)
    {
        $pwdvalue = Db::table('user')->where('id',$id)->value('password');

        if($pwd == $pwdvalue)
        {
            if($npwd == $newpwd)
            {
                $newpwd = md5($newpwd);
                $res = (new UserLogic())->savePwd($newpwd,$id);
                return $this->ajaxReturn('修改成功','',1);
            }else{
                return $this->ajaxReturn('两次密码输入不一致','',-2);

            }
        }else{
            return $this->ajaxReturn('原始密码不正确','',-1);
        }
    }


    /**
     * 新闻资讯详情
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function news($id)
    {

        //公告单独查询
        $res = (new NewsLogic())->queryGonggao($id);
        return $res;

//        $res = (new NewsLogic())->getNewsInfo($id);
//        return $res;
    }

    public function ajaxReturn($msg, $data = [],$code=[])
    {
        return json(['msg' => $msg, 'data' => $data,'code'=>$code]);
    }

    /**
     * 贷款申请
     * @param $name
     * @param $sex
     * @param $mobile
     * @param $code
     * @param $value
     * @return \think\response\Json
     */
    public function apply($id,$name, $sex, $mobile, $code, $value)
    {
        $result = (new SecurityCodeLogic())->apply($id,$name,$sex,$mobile,$code,$value);
        return $result;
    }

    /**
     * 项目信息提交
     * @param $project_id
     * @param $amount
     * @param $repay_num
     * @param $address
     * @return false|int
     */
    public function projectSubmit($project_id, $amount, $repay_num, $address,$product_id,$product_name)
    {
        $result = (new ProjectLogic())->apply($project_id,$amount,$repay_num,$address,$product_id,$product_name);
        return $result;
    }

    /**
     * 个人信息添加
     * @param $id
     * @param $sex
     * @param $user_origin
     * @param $mar_status
     * @param $room_status
     * @param $user_education
     * @param $user_qq
     * @param $user_address
     * @return string
     */
    public function userInformation($id, $username,$sex, $user_origin, $mar_status, $room_status, $user_education, $user_qq, $user_address)
    {

        $result = (new UserLogic())->userInformation($id,$username,$sex,$user_origin,$mar_status,$room_status,$user_education,$user_qq,$user_address);
        return $result;
    }

    /**
     * 投资管理列表
     * @param $id
     * @param $pageNo
     * @param $pagesize
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function investManage($id, $pageNo, $pagesize,$repay_status)
    {
        $result = (new ProjectLogic())->investManage($id,$pageNo, $pagesize,$repay_status);
        return $result;
    }

    /**
     * @param $id
     * @param $pageNo
     * @param $pagesize
     * @param $repay_status
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function investManages($id, $pageNo, $pagesize,$repay_status)
    {
        $result = (new ProjectLogic())->investManages($id,$pageNo, $pagesize,$repay_status);
        return $result;
    }



    public function getManageInfos($id)
    {
        $res = (new ProjectLogic())->getManageInfos($id);
        return $res;
    }

    public function companyProfile($type)
    {
        $res = (new CompanyLogic())->getManageInfo($type);
        return $res;
    }

    public function contact($type)
    {
        $res = (new ContactLogic())->contact($type);
        return $res;
    }

    /**
     * 意见反馈
     * @param $uid
     * @param $content
     * @param $type
     * @param $answer_status
     * @param $pid
     * @return false|int
     */
    public function askQues($uid,$content,$type,$answer_status,$pid)
    {
        $res = (new QuestionLogic())->setQuestion($uid,$content,$type,$answer_status,$pid);
        return $res;
    }

    /**
     * @param $uid
     * @param $p_id
     * @param $pageNo
     * @param $pagesize
     * @return array
     */
    public function getQuestions($uid,$p_id,$pageNo,$pagesize)
    {
        $res = (new QuestionLogic())->getListByUid($uid,$p_id,$pageNo,$pagesize);
        return $res;
    }


    /**
     * @param $project_id
     * @return mixed
     */
    public function getHouseByProjectId($project_id)
    {
        $res = (new HouseLogic())->getHouseInfo($project_id);
        return $res;
    }

    public function check($telephone)
    {
        $res = (new InvestLogic())->check($telephone);
        return $res;
    }


    /**
     * 申请资方
     * @param $uid
     * @param $source
     * @param $type
     * @param $telephone
     * @return false|int
     */
    public function setInvest($uid,$source,$type,$telephone)
    {
        $res = (new InvestLogic())->addApply($uid,$source,$telephone,$type);
        return $res;
    }

    /**
     * 业务员项目列表
     * @param $uid
     * @param $type
     * @param $pagesize
     * @param $pageNo
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyProjects($uid,$type,$pagesize,$pageNo)
    {
        $res = (new ProjectLogic())->queryMyProject($uid,$type,$pagesize,$pageNo);
        return $res;
    }

    /**
     * 是否拨打
     * @param $id
     * @param $is_dial
     * @return false|int
     */
    public function setDial($id,$is_dial)
    {
        $res = (new ProjectLogic())->setDial($id,$is_dial);
        return $res;
    }

    /**
     * 邀请用户接口
     * @param $name
     * @param $phone
     * @param $sex
     * @param $p_uid
     * @param $p_mid
     * @return false|int
     */
    public function setInviteUser($name,$phone,$sex,$p_uid,$p_mid)
    {
        $res = (new InviteUserLogic())->setInvite($name,$phone,$sex,$p_uid,$p_mid);
        return $res;
    }

    /**
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getUserStatus($uid)
    {
        $res = (new UserLogic())->queryUserStatus($uid);
        return $res;
    }

    /**
     * @param $id
     * @return false|int
     */
    public function setClick($id)
    {
        $res = (new NewsLogic())->queryClick($id);
        return $res;
    }

    public function getRecommend($base_money,$pagesize,$d_value)
    {
        $res = (new ProductLogic())->queryList($base_money,$pagesize,$d_value);
        return $res;
    }





}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/9
 * Time: 10:08
 */

namespace app\api\helper;


use app\component\logic\CardPriceLogic;
use app\component\logic\DetailesLogic;
use app\component\logic\FlagPointLogic;
use app\component\logic\FlagsLogic;
use app\component\logic\LogLogic;
use app\component\logic\MessageLogic;
use app\component\logic\OrderLogic;
use app\component\logic\PayLogLogic;
use app\component\logic\UserBrowseLogic;
use app\component\logic\UserBuyLogic;
use app\component\logic\UserCollectionLogic;
use app\component\logic\UserImgLogic;
use app\component\logic\UserInviteLogic;
use app\component\logic\UserLogic;
use app\component\logic\UserPointLogic;
use app\component\model\OrdersModel;
use app\component\model\PayLogModel;
use app\component\model\UserBrowseModel;
use app\component\model\UserCollectionModel;
use app\component\model\UserInviteModel;
use app\component\model\UserPointModel;
use think\Db;

class UserApiHelper
{
    /**
     * @param $username
     * @param $weixin
     * @param $openid
     * @param $logo
     * @param $sex
     * @return array|false|int|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userLogin($username, $openid,$logo,$sex)
    {
//        //验证验证码是否正确
////        $check = (new MessageLogic())->checkInfo($phone,$code);
////        if(!$check){
////            return '1001';
////        }
        $data = [
            'username' => $username,
            'openid' => $openid,
            'logo' => $logo,
            'sex' => $sex,
        ];
        $res = (new UserLogic())->userLogin($data);
        return $res;
    }

    /**
     * @param $uid
     * @param $mobile
     * @param $name
     * @param $email
     * @param $address
     * @param $introduce
     * @return false|int
     */
    public function updateUserInfo($uid,$mobile,$name,$email,$address,$introduce,$flags,$company,$position,$weixin)
    {
        $data = [
            'mobile' => $mobile,
            'name' => $name,
            'email' => $email,
            'address' => $address,
            'introduce' => $introduce,
            'company' => $company,
            'position' => $position,
            'weixin' => $weixin
        ];
        $res = (new UserLogic())->updateUserInfo($data,$uid,$flags);

        return $res;
    }

    /**
     * @param $uid
     * @param $type
     * @param $img_url
     * @return false|int
     */
    public function uploadUserImg($uid,$type,$img_url,$length)
    {
        if($type == 3){
            //上传到用户图片关联表
            $data = [
                'uid' => $uid,
                'type' => $type,
                'img_url' => $img_url
            ];
            $res = (new UserImgLogic())->uploadUserImg($data);
        }else{
            //上传到用户表
            $res = (new UserLogic())->uploadUserImg($uid,$img_url,$type,$length);
        }
        return $res;
    }

    public function delUserImg($id)
    {
        $res = (new UserImgLogic())->delUserImg($id);
        return $res;
    }

    /**
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo($uid,$v_id)
    {
        $res = (new UserLogic())->getUserInfo($uid,$v_id);
        return $res;
    }

    /**
     * @param $uid
     * @return false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateBrowseNum($uid,$browse_id)
    {
        $res = (new UserLogic())->updateBrowseNum($uid,$browse_id);
        return $res;
    }

    /**
     * @param $uid
     * @param $point_uid
     * @param $status
     * @return false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateUserPoint($uid,$point_uid,$status)
    {
        $data = [
            'uid' => $uid,
            'point_uid' => $point_uid,
            'status' => $status
        ];
        $res = (new UserPointLogic())->updateUserPoint($data);
        return $res;
    }

    /**
     * @param $uid
     * @param $collect_uid
     * @param $status
     * @return false|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateUserCollection($uid,$collect_uid,$status)
    {
        $data = [
            'uid' => $uid,
            'collection_id' => $collect_uid,
            'status' => $status
        ];
        $res = (new UserCollectionLogic())->updateUserCollection($data);
        return $res;
    }

    /**
     * @param $uid
     * @param $type
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyCards($uid,$type,$pageNo,$pageSize)
    {
        if($type == 1){
            //获取推荐名片列表
            $lists = (new UserInviteLogic())->getInvites($uid,$pageNo,$pageSize);
        }elseif ($type == 2){
            //获取收藏的名片列表
            $lists = (new UserCollectionLogic())->getCollection($uid,$pageNo,$pageSize);
        }else{
            //获取浏览名片列表
            $lists = (new UserBrowseLogic())->getBrowse($uid,$pageNo,$pageSize);
        }

        return $lists;
    }

    /**
     * @param $uid
     * @param $invite_id
     * @param $source_id
     * @param $username
     * @return false|int
     */
    public function UpdateUserInvite($uid,$invite_id,$source_id,$username)
    {
        $data = [
            'uid' => $uid,
            'invite_id' => $invite_id,
            'source_id' => $source_id,
            'username' => $username
        ];
        $res = (new UserInviteLogic())->UpdateUserInvite($data);
        return $res;
    }

    /**
     * @param $uid
     * @param $point_uid
     * @param $flag_id
     * @return false|int|string
     */
    public function updateFlagPoint($uid,$point_uid,$flag_id,$status)
    {
        $data = [
            'uid' => $uid,
            'point_uid' => $point_uid,
            'flag_id' => $flag_id,
            'status' => $status
        ];
        $res = (new FlagPointLogic())->updateFlagPoint($data);
        return $res;
    }

    public function updateStick($id,$is_stick,$type)
    {
        if($is_stick == 1){
            $data['is_stick'] = 0;
        }else{
            $data['is_stick'] = 1;
        }
        if($type == 1){
            //收藏名片置顶设置
            $res = (new UserCollectionLogic())->updateStick($data,$id);
        }elseif($type == 2){
            //浏览名片置顶设置
            $res = (new UserBrowseLogic())->updateStick($data,$id);
        }elseif($type == 3){
            //推荐名片置顶设置
            $res = (new UserInviteLogic())->updateStick($data,$id);
        }
        return $res;
    }

    public function updateShield($id,$is_stick,$type)
    {
        if($is_stick == 1){
            $data['is_shield'] = 2;
        }else{
            $data['is_shield'] = 1;
        }
        if($type == 1){
            //收藏名片置顶设置
            $res = (new UserCollectionLogic())->updateShield($data,$id);
        }elseif($type == 2){
            //浏览名片置顶设置
            $res = (new UserBrowseLogic())->updateShield($data,$id);
        }elseif($type == 3){
            //推荐名片置顶设置
            $res = (new UserInviteLogic())->updateShield($data,$id);
        }
        return $res;
    }

    public function userCount($uid)
    {
        //浏览数统计
        $browse = (new UserBrowseLogic())->userCount($uid);
        //收藏数统计
        $collection = (new UserCollectionLogic())->userCount($uid);
        //点赞统计
        $point = (new UserPointLogic())->userCount($uid);
        //推荐统计
        $invite = (new UserInviteLogic())->userCount($uid);
        $count = [];
        for ($i = 0; $i < 2; $i++){
            $count[$i]['browse'] = $browse[$i];
            $count[$i]['collection'] = $collection[$i];
            $count[$i]['point'] = $point[$i];
            $count[$i]['invite'] = $invite[$i];
        }
        return $count;
    }

    public function getFlags()
    {
        $res = (new FlagsLogic())->getAllList();
        return $res;
    }

    public function delUserPic($uid,$type)
    {
        if($type == 1){
            $data['img'] = '';
        }elseif ($type == 2){
            $data['video'] = '';
        }elseif ($type == 4){
            $data['com_logo'] = '';
        }elseif ($type == 5){
            $data['voice'] = '';
        }
        $res = (new UserLogic())->delUserPic($uid,$data);
        return $res;
    }

    public function updateUserStatus($uid,$parent_id,$invite_id)
    {
        //获取邀请人的类型
        $inviteInfo = (new UserLogic())->getInfoByUid($invite_id);
        if($inviteInfo['type'] == 1){
            //邀请人为普通用户，则邀请人更改为客服
            $invite_id = 191;
        }
        //获取该购买人的销售用户id
        $res = (new UserLogic())->getInfoByUid($parent_id);
        $sale_id = $res['sale_id'];
        if(empty($sale_id) && $res['is_sale'] == 2){
            //该上级为销售，则该用户的销售id为该上级
            $sale_id = $parent_id;
        }
        $data = [
            'parent_id' => $parent_id,
            'invite_id' => $invite_id,
            'type' => 2,
            'pay_time' => time(),
            'sale_id' => $sale_id
        ];

//        $res = (new PayLogModel())->data(['text'=>json_encode($data,JSON_UNESCAPED_UNICODE),'sn'=>'13','order_id'=>'456'])->save();
        $res = (new UserLogic())->updateInfo($data,$uid);
        //该销售人员的销售总数user_sale_num + 1
        $saleInfo = (new UserLogic())->getInfoByUid($sale_id);
        $where = [ 'user_sale_num' => ($saleInfo['user_sale_num'] + 1)];
        (new UserLogic())->updateInfo($where,$sale_id);

        //自己收藏自己的名片
        //将自己的名片添加到该用户的收藏列表中，并将收藏数量加1
        $data = ['uid'=>$uid,'collection_id'=>$uid,'status'=>0,'is_allow'=>2,'sort'=>99];
        $res = (new UserCollectionLogic())->updateUserCollection($data);
        return $res;
    }

    public function addOrder($uid,$price,$parent_id,$invite_id)
    {
        //添加名片购买订单到订单表
        $res = (new OrderLogic())->addUserOrder($uid,$price,$parent_id,$invite_id);
        return $res;
    }

    public function behaviorCount1($uid)
    {

        //总下线数量
        $subordinate = (new UserLogic())->subordinateCount($uid);
        //总浏览数量
        $where = ['uid'=>$uid,'status'=>1];
        $browse = (new UserBrowseLogic())->Count($where);
        //总收藏数量
        $collection = (new UserCollectionLogic())->Count($where);
        //总点赞数量
        $point = (new UserPointLogic())->Count($where);
        //推荐统计
        $invite = (new UserInviteLogic())->Count($where);
        // 雷达点赞数量
        $map['operation_id'] = $uid;
        $praise = (new LogLogic())->count($map,1);
        // 雷达收藏
        $collect = (new LogLogic())->count($map,2);
        // 雷达转发
        $transmit = (new LogLogic())->count($map,3);
        // 雷达商城
        $shopMall = (new LogLogic())->count($map,4);
        // 雷达查看
        $see = (new LogLogic())->count($map,5);
        // 雷达聊天
        $hat = (new LogLogic())->count($map,6);
        // 雷达官网
        $officialWebsite= (new LogLogic())->count($map,7);
        // 雷达动态
        $dynamic = (new LogLogic())->count($map,8);
        // 雷达视频
        $video = (new LogLogic())->count($map,9);
        // 雷达语音
        $voice = (new LogLogic())->count($map,10);
        // 雷达广场
        $square = (new LogLogic())->count($map,11);
        // 分类访问数
        $classify = (new LogLogic())->count($map,12);
        // 雷达产品
        $product = (new LogLogic())->count($map,13);
        // 雷达购买
        $buy = (new LogLogic())->count($map,14);
        // 雷达名片支付
        $pay = (new LogLogic())->count($map,15);
        // 雷达名片支付成功数
        $payStatus = (new LogLogic())->count($map,16);
        // 雷达收货地址
        $address = (new LogLogic())->count($map,17);
        // 雷达订单
        $order = (new LogLogic())->count($map,18);
        // 雷达商品付款成功
        $goodsStatus = (new LogLogic())->count($map,19);
        // 雷达取消订单
        $cancelOrder = (new LogLogic())->count($map,20);
        // 动态点赞
        $dynamicPraise = (new LogLogic())->count($map,21);
        $data = [
            'subordinate'   => $subordinate,
            'browse'        => $browse,
            'collection'    => $collection,
            'point'         => $point,
            'invite'        => $invite,
            'praise'        => $praise,
            'collect'       => $collect,
            'transmit'      => $transmit,
            'shopMall'      => $shopMall,
            'see'           => $see,
            'hat'           => $hat,
            'officialWebsite' => $officialWebsite,
            'dynamic'         => $dynamic,
            'video'           => $video,
            'voice'           => $voice,
            'square'          => $square,
            'classify'        => $classify,
            'product'         => $product,
            'buy'             => $buy,
            'pay'             => $pay,
            'payStatus'       => $payStatus,
            'address'         => $address,
            'order'           => $order,
            'goodsStatus'     => $goodsStatus,
            'cancelOrder'     => $cancelOrder,
            'dynamicPraise'   => $dynamicPraise
        ];
        return $data;
    }
    public function behaviorCount($uid)
    {
        //总下线数量
        $subordinate = (new UserLogic())->subordinateCount($uid);
        //总浏览数量
        $where = ['uid'=>$uid,'status'=>1];
        $browse = (new UserBrowseLogic())->Count($where);
        //总收藏数量
        $collection = (new UserCollectionLogic())->Count($where);
        //总点赞数量
        $point = (new UserPointLogic())->Count($where);
        //推荐统计
        $invite = (new UserInviteLogic())->Count($where);
        // 以下新的
        $map['operation_id'] = $uid;
        // 商城浏览总数
        $shopMall = (new LogLogic())->count($map,4);
        // 咨询聊天总数
        $hat = (new LogLogic())->count($map,6);
        // 查看官网总数
        $officialWebsite= (new LogLogic())->count($map,7);
        // 查看动态总数
        $dynamic = (new LogLogic())->count($map,8);
        // 播放视频总数
        $video = (new LogLogic())->count($map,9);
        // 播放语音总数
        $voice = (new LogLogic())->count($map,10);
        // 进入广场总数
        $square = (new LogLogic())->count($map,11);
        // 查看商品总数
        $product = (new LogLogic())->count($map,13);
        // 购买商品总数
        $goodsStatus = (new LogLogic())->count($map,19);
        // 购买专属名片总数
        $payStatus = (new LogLogic())->count($map,16);
        $data = [
            'subordinate'   => $subordinate,
            'browse'        => $browse,
            'collection'    => $collection,
            'point'         => $point,
            'invite'        => $invite,
            'shopMall'      => $shopMall,
            'hat'           => $hat,
            'officialWebsite'=>$officialWebsite,
            'dynamic'       => $dynamic,
            'video'         => $video,
            'voice'         => $voice,
            'square'        => $square,
            'product'       => $product,
            'goodsStatus'   => $goodsStatus,
            'payStatus'     => $payStatus
        ];
        return $data;
    }
    /** 行为统计详情
     * @param $uid
     * @param $type
     * @param $page
     * @param $page_size
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function behaviorInfo($uid,$type,$page,$page_size)
    {
        if(empty($page)){
            $page = 1;
        }
        if(empty($page_size)){
            $page_size = 10;
        }
        $type = (int)$type;
        $map['log.operation_id'] = $uid;
        $map['log.type'] = $type;
        $order = "id desc";
        if($type == 5){ // 浏览
            $model = new UserBrowseModel();
            $res = $model->alias('b')->join('user u','u.id = b.browse_id')->where('b.uid',$uid)->where('b.browse_id','<>',$uid)->limit(($page-1)*$page_size,$page_size)->field('u.logo,u.username,u.id user_id,b.*')->order("b.id desc")->select();
            foreach ($res as &$val){
                $id = $val['id'];
                $num = $model->where(['uid'=>$val['uid'],'browse_id'=>$val['browse_id']])->where('id','<=',$id)->count();
                $val['seeNum'] = $num;
                $val['type'] = 5;
            }
        }elseif ($type == 2){ // 收藏
            $model = new UserCollectionModel();
            $res = $model->alias('c')->join('user u','u.id = c.collection_id')->where(['c.uid'=>$uid,'c.status'=>1])->where('c.collection_id','<>',$uid)->limit(($page-1)*$page_size,$page_size)->field('u.logo,u.username,u.id,c.*')->order("c.id desc")->select();
            foreach ($res as &$val){
                $val['type'] = 2;
            }
        }elseif ($type == 1){ // 点赞
            $model1 = new UserPointModel();
            $res = $model1->alias('p')->join('user u','p.point_uid = u.id','left')->where(['p.uid'=>$uid,'p.status'=>1])->where('p.point_uid','<>',$uid)->limit(($page -1 ) * $page_size , $page_size)->field('u.logo,u.username,u.id,p.*')->order("p.id desc")->select();
            foreach ($res as &$val){
                $val['type'] = 1;
            }
        }elseif ($type == 3){ // 推荐
            $model1 = new UserInviteModel();
            $res = $model1->alias('i')->join('user u','i.source_id = u.id','left')->where('i.uid',$uid)->where('i.source_id','<>',$uid)->limit(($page -1 ) * $page_size , $page_size)->field('u.logo,u.username,i.uid,i.invite_id,i.source_id,i.is_stick,i.is_shield,i.status,i.create_time,i.update_time,u.id')->order("i.id desc")->select();
            foreach ($res as &$val){

                $val['type'] = 3;
            }
        }else{
            $res = (new LogLogic())->returnList($map,$page,$page_size);
        }

        return $res;
    }
    public function onLine($uid,$on_line_uid)
    {
        $res = (new UserLogic())->onLine($uid,$on_line_uid);
        return $res;
    }

    public function getUserInfos($uid)
    {
        $res = (new UserLogic())->getUserInfos($uid);
        return $res;
    }


    public function addIntegralOrder($uid,$price)
    {
        $data['sn'] = date('YmdHis').rand(1111,9999);
        $data['price'] = $price;
        $data['mount'] = 1;
        $data['uid'] = $uid;
        $data['type'] = 3;
        $data['is_commission'] = 2;
        $res = (new OrderLogic())->addIntegralOrder($data);
        return $res;
    }







}
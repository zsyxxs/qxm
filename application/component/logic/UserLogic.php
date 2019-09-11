<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:11
 */

namespace app\component\logic;

use app\api\helper\ApiReturn;
use app\component\interfaces\weixin\api\WxresultApi;
use app\component\model\UserModel;
use think\Db;

class UserLogic   extends BaseLogic
{
    public function rank($start,$end)
    {
        $start = strtotime($start);
        $end = strtotime($end);
       $query = Db::table('user')->alias('u')
           ->join('order o','u.id=o.uid')
           ->group('u.p_id')
           ->where('o.status',1)
           ->where('o.update_time','gt',$start)
           ->where('o.update_time','lt',$end)
           ->field('count(*) as count,u.p_id')
           ->select();

       $num = count($query);

        for($j =0;$j < $num -1; $j++){
            for($i = 0; $i < $num -1; $i++){
                if($query[$i]['count'] > $query[$i+1]['count']){
                    $temp = $query[$i + 1];
                    $query[$i + 1] =  $query[$i];
                    $query[$i] =  $temp;
                }
            }
        }
        $query = array_reverse($query);

        $list = [];
        foreach ($query as $k => $v){
            if($k >2){
                break;
            }
            $query[$k]['info'] = Db::table('user')
                                ->where('id',$v['p_id'])
                                ->field('id,username,logo,level,mobile,weixin,level_one,level_two')
                                ->find();

            array_push($list,$query[$k]);
        }
       return $list;
    }

    /**
     * ajax验证用户名
     * @param $data
     * @return array
     */
    public function check_name($data)
    {
        //验证名字的长度
        $len = mb_strlen($data['username']);
        if($len > 8){
            return ApiReturn::error('用户名过长');
        }
        //判断用户名是否重复
        $res = (new UserLogic())->getInfo(['username'=>$data['username']],false,'id');
        if($res && $res['id'] != $data['uid']){
            return ApiReturn::error('用户名已存在');
        }else{
            return ApiReturn::success('用户名可用');
        }
    }

    /**
     * 注册
     * @param $data
     * @return array|bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register($data)
    {
        //判断参数是否正确
        $field = ['username','openid','logo','unionid'];
        $res =  $this->checkFields($field,$data);
        //参数基本信息验证完成
        if($res !== ENABLE){
            return $res;
        }
        //判断当前用户是否已经注册
        $res = (new UserLogic())->getInfo(['unionid' => $data['unionid']]);
        if($res){
            return ApiReturn::success('已经注册',$res);
        }
        //注册信息
        $res = $this->save($data);
        if(!$res){
            return ApiReturn::error('授权失败，请重新授权');
        }
        $userInfo = $this->getInfo(['unionid' => $data['unionid']]);
        return ApiReturn::success('授权成功',$userInfo);


    }

    /**
     * 登录
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login($data)
    {
        $field = ['id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $map = [
            'id' => $data['id']
        ];
        $userInfo = $this->getInfo($map);
        if(empty($userInfo)){
            return ApiReturn::error('用户名信息不存在');
        }
        //用户信息存在
        return ApiReturn::success('登录成功',$userInfo);


    }

    public function getUserInfoByUnionid($data)
    {
        $field = ['unionid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $map = [
            'unionid' => $data['unionid']
        ];
        $userInfo = $this->getInfo($map);
        if(empty($userInfo)){
            return ApiReturn::error('用户名信息不存在');
        }
        //用户信息存在
        return ApiReturn::success('登录成功',$userInfo);
    }


    /**
     * 绑定邀请码
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_code($data)
    {
        $field = ['uid','invite_code'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        //判断邀请码是否为特殊邀请码
        if($data['invite_code'] == '123456'){
            //假设123456为特殊邀请码
            //直接将用户等级升级为2级，并生成邀请码，可以进行邀请用户，不生成任务，绑定上级和邀请码
            //获取这个邀请码的用户id
            $inviteInfo = (new UserLogic())->getInfo(['code'=>$data['invite_code']]);
            //该邀请码的下级数量+1
            (new UserLogic())->setInc(['id'=>$inviteInfo['id']],'level_one',1);
            $map = [
                'level' => 2,
                'code' => rand(10000,99999),
                'invite_code' => $data['invite_code'],
                'p_id' => $inviteInfo['id']
            ];
            $res = (new UserLogic())->save($map,['id' => $data['uid']]);
        }else{
            //普通邀请码，只单纯的绑定邀请码
            $map = ['invite_code' => $data['invite_code']];
            $res = (new UserLogic())->save($map,['id' => $data['uid']]);
        }

        if($res){
            return ApiReturn::success('绑定成功');
        }else{
            return ApiReturn::error('绑定失败');
        }


    }


    /**
     * 获得用户列表
     * @param $pagesize
     * @param $username
     * @return array
     * @throws \think\exception\DbException
     */
    public function getUserListss($pagesize,$username)
    {
        $order = 'create_time desc';
        $query = Db::table('user')
            ->order($order);
        $count = Db::table('user')->count();
        if(!empty($username)){
            $query->where('username','like',"%$username%");
        }



        $lists = $query->fetchSql(false)->paginate($pagesize);
        return ['list'=>$lists,'count'=>$count];
    }


    /**
     * @param $status
     * @param $id
     * @return false|int
     */
    public function setStatus($status,$id)
    {
        if($status == '1'){
            $status = 0;
        }else{
            $status = 1;
        }
        $model = new UserModel();
        $res = $model->save(['status'=>$status],['id'=>$id]);
        return $res;
    }


    /**
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateInfo($data)
    {
        $field = ['uid','username','mobile','code','weixin','sex','height','weight','province','city','invite_code'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        unset($data['s']);
        Db::startTrans();
        try{
            //验证名字的长度
            $len = mb_strlen($data['username']);
            if($len > 8){
                return ApiReturn::error('用户名太长');
            }
            //判断用户名是否重复
            $res = (new UserLogic())->getInfo(['username'=>$data['username']],false,'id');
            if($res && $res['id'] != $data['uid']){
                return ApiReturn::error('用户名已存在');
            }
            //验证验证码
            $check = (new MessageLogic())->checkPhoneCode($data['mobile'],$data['code']);
            if(!$check){
                return ApiReturn::error('验证码错误');
            }
            unset($data['code']);
            //判断微信号是否存在
            $res = (new UserLogic())->getInfo(['weixin'=>$data['weixin']],false,'id');
            if($res && $res['id'] != $data['uid']){
                return ApiReturn::error('微信号已占用');
            }

            //判断邀请码
            $inviteInfo = (new UserLogic())->getInfo(['code'=>$data['invite_code']]);
            if(empty($inviteInfo)){
                return ApiReturn::error('邀请码不存在');
            }




            if($inviteInfo['type'] == 1){
                //超级邀请码
                //该邀请码的下级数量+1
                $res = (new UserLogic())->setInc(['id'=>$inviteInfo['id']],'level_one',1);
                //用户升级到2级，不生成任务，绑定上级，生成邀请码即微信号
                $data['level'] = 2;
                $data['code'] = $data['weixin'];
                $data['p_id'] = $inviteInfo['id'];
            }

            //更新用户信息

            $res = (new UserLogic())->save($data,['id' => $data['uid']]);

            $userInfo = (new UserLogic())->getInfo(['id'=>$data['uid']]);
            Db::commit();
            return ApiReturn::success('成功',$userInfo);
        }catch (\Exception $e){
            Db::rollback();
//            return ApiReturn::error($e->getMessage());
            return ApiReturn::error('更新失败');
        }

    }

    /**
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveInfo($data)
    {
        $field = ['id','username','level'];
        $res = $this->checkFields($field,$data['data']);
        if($res !== ENABLE){
            return '缺少参数';
        }
        $res = (new UserLogic())->save($data['data'],['id' => $data['data']['id']]);
        if($res){
            return '成功';
        }else{
            return '失败';
        }
    }

    /**
     * 因为邀请码，暂时弃用
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateInfo1($data)
    {
        $field = ['uid','mobile','code','weixin','sex','height','weight','province','city','invite_code'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }

        Db::startTrans();
        try{
            //验证验证码
            $check = (new MessageLogic())->checkPhoneCode($data['phone'],$data['code']);
            if(!$check){
                return ApiReturn::error('验证码错误');
            }
            //判断邀请码
            $str = substr($data['invite_code'],0,4);
            if($str == 'qxmf'){
                //免费邀请码，直接将用户等级升级为2级，并生成邀请码，可以进行邀请用户，不生成任务，绑定上级和邀请码
                //获取这个免费邀请码的用户id
                $inviteInfo = (new UserLogic())->getInfo(['free_code'=>$data['invite_code']]);
                if(empty($inviteInfo)){
                    return ApiReturn::error('该邀请码已失效');
                }
                //删除免费邀请码,该邀请码的下级数量+1
                (new UserLogic())->save([
                    'free_code' => '',
                    'level_one' => $inviteInfo['level_one'] +1
                ],['id' => $inviteInfo['id']]);

                $data['level'] = 2;
                $data['code'] = $this->createCode($data['uid']);
                $data['p_id'] = $this->$inviteInfo['id'];

            }elseif($str == 'qxm'){
                //普通邀请码
                $inviteInfo = (new UserLogic())->getInfo(['code'=>$data['invite_code']]);
                if(empty($inviteInfo)){
                    return ApiReturn::error('邀请码不存在');
                }
                if($inviteInfo['type'] == 1){
                    //超级邀请码
                    //该邀请码的下级数量+1
                    (new UserLogic())->setInc(['id'=>$inviteInfo['id']],'level_one',1);

                    $data['level'] = 2;
                    $data['code'] = $this->createCode($data['uid']);
                    $data['p_id'] = $this->$inviteInfo['id'];
                }
            }else{
                $inviteInfo = (new UserLogic())->getInfo(['code'=>$data['invite_code']]);
                if(empty($inviteInfo)){
                    return ApiReturn::error('邀请码不存在');
                }
            }


            //更新用户信息
            unset($data['code']);
            $res = (new UserLogic())->save($data,['id' => $data['uid']]);
            $userInfo = (new UserLogic())->getInfo(['id'=>$data['uid']]);
            Db::commit();
            return ApiReturn::success('成功',$userInfo);
        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('更新失败');
        }






    }


    /**
     * 用户下线统计
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function count_user($data)
    {
        $field = ['uid','pageNo','pagesize'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $pageNo = $data['pageNo'];
        $pagesize = $data['pagesize'];
        //获取当前用户的一级下线
        $query = Db::table('user')
            ->where('p_id',$uid)
            ->field('username,logo,level_one');
        $offset = $this->getOffset($pageNo,$pagesize);

        $info = $query->limit($offset,$pagesize)->select();
        return ApiReturn::success('成功',$info);

    }


    /**
     * 用户个人主页
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo($data)
    {
        $field = ['uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $userInfo = (new UserLogic())->getInfo(['id'=>$uid]);
        //获取用户的标签信息
        $flag_user = explode(',',$userInfo['flag_user']);
        $flag_like = explode(',',$userInfo['flag_like']);

        $flag_users = Db::table('flags')->where('id','in',$flag_user)->field('id,title')->select();
        $flag_likes = Db::table('flags')->where('id','in',$flag_like)->field('id,title')->select();
        $userInfo['flag_users'] = $flag_users;
        $userInfo['flag_likes'] = $flag_likes;
        //获取该用户设置到首页的评价
        $map = [
            't.p_id' => $uid,
            't.status' => 1,
            't.type' => 1,
            't.assess' => array('gt',0),
            'home_page' => 1
            ];
        $order = 't.home_page desc,t.update_time desc';
        $assess = Db::table('task')->alias('t')
            ->join('user u','t.uid=u.id')
            ->where($map)
            ->order($order)
            ->field('t.id,t.assess,t.assess_content,t.home_page,u.username,u.logo')
            ->find();
        $userInfo['assess'] = $assess;
        //获取用户的最新的一条朋友圈
        $dynamic = Db::table('dynamic')
            ->where(['p_id' => 0,'uid'=>$uid,'status'=>1])
            ->order('create_time desc')
            ->field('id,uid,content,voice,length,imgs,type,cate,status,create_time,update_time')
            ->find();
        $imgs = explode(',',$dynamic['imgs']);
        $arr_imgs = [];
        foreach ($imgs as $k)
        {
            $map = [
                'id' => array('in',$imgs),
                'status' => ENABLE
            ];
            $arr_imgs = (new ImgLogic())->getLists($map,false,'id,url,status');
        }
        $dynamic['arr_imgs'] = $arr_imgs;
        $userInfo['dynamic'] = $dynamic;

        return ApiReturn::success('success',$userInfo);

    }






}
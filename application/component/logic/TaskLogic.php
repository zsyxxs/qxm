<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\CashModel;
use app\component\model\DyBannersModel;
use app\component\model\UserModel;
use think\Db;

class TaskLogic  extends BaseLogic
{

    /**
     * 完成任务
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function finish($data)
    {
        $fields = ['t_id','p_id','uid'];
        $res = $this->checkFields($fields,$data);
        if($res !== ENABLE){
            return $res;
        }

        //查找任务是否存在
        $map = [
            'id' => $data['t_id'],
            'p_id' => $data['p_id'],
            'uid' => $data['uid'],
            'status' => DISABLE
        ];
        $res = (new TaskLogic())->getInfo($map);
        if(empty($res)){
            return ApiReturn::error('任务信息错误');
        }

        //更改任务状态为已完成
        $res = (new TaskLogic())->save(['status' => ENABLE],['id' => $data['t_id']]);
        if($res){
            return ApiReturn::success('完成成功');
        }else{
            return ApiReturn::error('完成失败，请重试');
        }




    }


    /**
     * 评价任务
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assess($data)
    {
        $fields = ['t_id','p_id','uid','assess'];//assess_content评价内容非必填
        $res = $this->checkFields($fields,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $p_id = $data['p_id'];
        $t_id = $data['t_id'];
        $assess = $data['assess'];
        $assess_content = 0;
        if(isset($data['assess_content'])){
            $assess_content = $data['assess_content'];
        }

        //查找任务是否存在
        $map = [
            'id' => $t_id,
            'p_id' => $p_id,
            'uid' => $uid,
            'status' => ENABLE
        ];
        $taskInfo = (new TaskLogic())->getInfo($map);
        if(empty($taskInfo)){
            return ApiReturn::error('任务信息错误');
        }
        if($taskInfo['assess'] > 0){
            return ApiReturn::error('请勿重复评价');
        }

        Db::startTrans();
        try{
            //更新该任务的评价状态
            (new TaskLogic())->save([
                'assess' => $assess,
                'assess_content' => $assess_content
            ],['id' => $t_id]);
            //判断该任务的等级是否为一级，且此次评价为差评，则为用户和用户的第二级上级重新生成一个任务
            //该上级佣金获得100
            //获取该任务的佣金信息
            $commissionInfo = (new CommissionLogic())->getInfo(['t_id'=>$t_id]);
            if($data['assess'] == 1 && $taskInfo['level'] ==1){
                //给该任务的佣金获得者发放佣金
                (new UserLogic())->setInc(['id'=>$commissionInfo['uid']],'money',LEVEL_BAD_MONEY);
                //更新该任务的上级佣金发放状态为已发放，佣金改为10000
                (new CommissionLogic())->save(['status' => 1,'money' => LEVEL_BAD_MONEY],['t_id' => $t_id]);

                //获取用户的上级信息
                $parentInfo = (new UserLogic())->getInfo(['id' => $p_id]);
                //为用户生成一个新的二级任务
                //根据用户选择的标签，推送与之匹配的三个会员用户
                $grandId = $parentInfo['p_id'];
                $visitors = $taskInfo['visitor_ids'];
                $this->create_task_two($uid,$grandId,$visitors);

            }else{
                //评价为中评或好评，或者任务等级为二级，则直接将该任务的佣金发放给佣金获得者
                (new UserLogic())->setInc(['id'=>$commissionInfo['uid']],'money',$commissionInfo['money']);
                //更新该任务的上级佣金发放状态为已发放
                (new CommissionLogic())->save(['status' => 1],['t_id' => $t_id]);

                //一级用户升级为二级用户，且生成邀请码
                $userInfo = (new UserLogic())->getInfo(['id'=>$uid],false,'id,weixin');
                $map = ['level' => 2,'code' => $userInfo['weixin']];
                (new UserLogic())->save($map,['id' =>$uid]);
            }

            Db::commit();
            return ApiReturn::success('评价成功');
        }catch(\Exception $e){
            Db::rollback();
//            return $e->getMessage();
            return ApiReturn::error('评价失败，请重试');
        }


    }





    /**
     * 任务列表
     * @param $order
     * @param $pagesize
     * @return array
     * @throws \think\exception\DbException
     */
    public function getUserListss($order,$pagesize,$status)
    {
        $query = Db::table('task')->alias('t')
            ->join('user u','t.uid = u.id')
            ->field('t.*,u.username')
            ->order($order);

        if(!empty($status)){
            if($status == '1'){
                $query = $query->where('t.status',0);
            }elseif ($status == 2){
                $query = $query->where('t.status',1);
            }

        }

        $lists = $query->fetchSql(false)->paginate($pagesize);

        $count = Db::table('task')->count();

        return ['list'=>$lists,'count'=>$count];
    }

    /**
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTaskDetails($id)
    {
        $task = Db::table('task')->alias('t')
            ->join('user u','t.uid=u.id')
            ->where('t.id',$id)
            ->field('t.*,u.username')
            ->find();
        $parent = (new UserLogic())->getInfo(['id'=>$task['p_id']]);
        $task['parentName'] = $parent['username'];
        return $task;

    }

    /**
     * 获取游客的人员名
     * @param $taskInfo
     * @return array
     */
    public function getVisitor($taskInfo)
    {
        $v_ids = explode(',',$taskInfo['visitor_ids']);
        $visitor_name = [];
        foreach ($v_ids as $v){
            $user = (new UserLogic())->getInfo(['id'=>$v]);
            if($user){
                array_push($visitor_name,$user['username']);
            }
        }
        return $visitor_name;
    }

    /**
     * 任务点赞详情
     * @param $taskInfo
     * @return array
     */
    public function getPoint($taskInfo)
    {
        $point_name = [];
        if($taskInfo['point_ids'] != 0){
            $p_ids = explode(',',$taskInfo['point_ids']);
            foreach ($p_ids as $v){
                $user = (new UserLogic())->getInfo(['id'=>$v]);
                if($user){
                    array_push($point_name,$user['username']);
                }
            }
        }
        return $point_name;


    }

    /**
     * 任务列表
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($data)
    {
        $field = ['uid','pageNo','pagesize','status'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $pageNo = $data['pageNo'];
        $pagesize = $data['pagesize'];
        $status = $data['status'];


//        $query = Db::table('task')
//            ->where('status',$status)
//            ->whereOr('p_id',$uid)
//            ->whereOr('uid',$uid)
//            ->whereOr('visitor_ids','like',"%$uid%");
        $result = Db::table('task')->where(function ($query) use ($status,$uid){
            if($status == 1){
                $map = ['status'=> $status,'p_id'=>$uid,'assess' => array('egt',1)];
            }else{
                $map = ['p_id'=>$uid,'assess' => 0];
            }
            $query->where($map);
        })->whereOr(function ($query) use ($status,$uid){
            if($status == 1){
                $map = ['status'=> $status,'uid'=>$uid,'assess' => array('egt',1)];
            }else{
                $map = ['uid'=>$uid,'assess' => 0];
            }
            $query->where($map);
        })->whereOr(function ($query) use ($status,$uid) {
            if($status == 1){
                $map = ['status'=> $status,'one'=>$uid,'assess' => array('egt',1)];
            }else{
                $map = ['one'=>$uid,'assess' => 0];
            }
            $query->where($map);
        })->whereOr(function ($query) use ($status,$uid) {
            if($status == 1){
                $map = ['status'=> $status,'two'=>$uid,'assess' => array('egt',1)];
            }else{
                $map = ['two'=>$uid,'assess' => 0];
            }
            $query->where($map);
        })->whereOr(function ($query) use ($status,$uid) {
            if($status == 1){
                $map = ['status'=> $status,'three'=>$uid,'assess' => array('egt',1)];
            }else{
                $map = ['three'=>$uid,'assess' => 0];
            }
            $query->where($map);
        })->order('id desc')
            ->field('id,p_id,uid,visitor_ids,point_ids,point_num,step_ids,step_num,status,level,assess,assess_content,voice,length,type,is_send,home_page,create_time,update_time');
//        whereOr(function ($query) use ($status,$uid){
//            if($status == 1){
//                $map = ['status' =>$status,'visitor_ids' => array('like',"%$uid%"),'assess' => array('egt',1)];
//            }else{
//                $map = ['visitor_ids' => array('like',"%$uid%"),'assess' => 0];
//            }
//            $query->where($map);
//        });

        $offset = $this->getOffset($pageNo,$pagesize);
        $list = $result->limit($offset,$pagesize)->select();
        foreach ($list as $k => $v){
            //获取上级用户信息
            $pInfo = (new UserLogic())->getInfo(['id' => $v['p_id']],false,'id,username,logo');
            $uInfo = (new UserLogic())->getInfo(['id' => $v['uid']],false,'id,username,logo');
            $visitor = explode(',',$v['visitor_ids']);
            $vInfo = (new UserLogic())->getLists(['id' => array('in',$visitor)],false,'id,username,logo');
            $list[$k]['pInfo'] = $pInfo;
            $list[$k]['uInfo'] = $uInfo;
            $list[$k]['vInfo'] = $vInfo;
        }
        return ApiReturn::success('成功',$list);

    }


    /**
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_list($data)
    {
        $field = ['uid','pageNo','pagesize','type'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $pageNo = $data['pageNo'];
        $pagesize = $data['pagesize'];
        $type = $data['type'];

        $list = $this->getTaskList($uid,$type,$pageNo,$pagesize);
        return $list;

    }

    /**
     * @param $uid
     * @param $type
     * @param $pageNo
     * @param $pagesize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTaskList($uid,$type,$pageNo,$pagesize)
    {
        if($type == 1){
            //任务列表
            $result = Db::table('task')->where(function ($query) use ($uid){
                $map = ['p_id'=>$uid];
                $query->where($map);
            })->whereOr(function ($query) use ($type,$uid){
                $map = ['uid'=>$uid];
                $query->where($map);
            });
        }else{
            //游客列表
            $result = Db::table('task')->where(function ($query) use ($uid) {
                $map = ['one'=>$uid];
                $query->where($map);
            })->whereOr(function ($query) use ($uid) {
                $map = ['two'=>$uid];
                $query->where($map);
            })->whereOr(function ($query) use ($uid) {
                $map = ['three'=>$uid,'assess' => 0];
                $query->where($map);
            });
        }

        $offset = $this->getOffset($pageNo,$pagesize);
        $list = $result->order('id desc')
            ->field('id,p_id,uid,user_level,visitor_ids,point_ids,point_num,step_ids,step_num,status,level,assess,assess_content,voice,length,type,is_send,home_page,create_time,update_time')
            ->limit($offset,$pagesize)
            ->select();
        foreach ($list as $k => $v){
            //获取上级用户信息
            $pInfo = (new UserLogic())->getInfo(['id' => $v['p_id']],false,'id,username,logo,level');
            $uInfo = (new UserLogic())->getInfo(['id' => $v['uid']],false,'id,username,logo,level');
            $visitor = explode(',',$v['visitor_ids']);
            $vInfo = (new UserLogic())->getLists(['id' => array('in',$visitor)],false,'id,username,logo,level');
            $list[$k]['pInfo'] = $pInfo;
            $list[$k]['uInfo'] = $uInfo;
            $list[$k]['vInfo'] = $vInfo;
        }
        return ApiReturn::success('成功',$list);
    }


    /**
     * 游客点赞任务
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function point_up($data)
    {
        $field = ['uid','id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $id = $data['id'];
        //获取当前任务信息
        $taskInfo = (new TaskLogic())->getInfo(['id' => $id]);
        //判断该用户是否点赞或者已经灭灯
        $point_ids = explode(',',$taskInfo['point_ids']);
        $step_ids = explode(',',$taskInfo['step_ids']);

        if(in_array($uid,$point_ids)){
            return ApiReturn::error('已点赞');
        }
        if(in_array($uid,$step_ids)){
            return ApiReturn::error('已灭灯');
        }

        if(empty($taskInfo['point_ids'])){
            $where['point_ids'] = $uid;
        }else{
            $where['point_ids'] = $taskInfo['point_ids'].','.$uid;
        }
        $where['point_num'] = $taskInfo['point_num'] + 1;

        //更新任务的点赞信息
        Db::startTrans();
        try{
            //更新任务点赞信息
            $res = (new TaskLogic())->save($where,['id' => $id]);
//            dump($where['point_num']);
            //判断当前该任务的点赞数量
            if($where['point_num'] == 1 && $taskInfo['step_num'] == 0 && $taskInfo['level'] != 2){
                //三个全部点赞，则为该任务用户发布一条动态
                $data = [
                    'uid' => $taskInfo['uid'],
                    't_id' => $taskInfo['id'],
                    'voice' => $taskInfo['voice'],
                    'length' => $taskInfo['length'],
                    'type' => 3,
                    'cate' => 1,
                ];
                $res = (new DynamicLogic())->save($data);

            }
            Db::commit();
            return ApiReturn::success('点赞成功');

        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error($e->getMessage());
        }



    }

    /**
     * 任务灭灯
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function step_down($data)
    {
        $field = ['uid','id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $id = $data['id'];
        //获取当前任务信息
        $taskInfo = (new TaskLogic())->getInfo(['id' => $id]);
        //判断该用户是否点赞或者已经灭灯
        $point_ids = explode(',',$taskInfo['point_ids']);
        $step_ids = explode(',',$taskInfo['step_ids']);

        if(in_array($uid,$point_ids)){
            return ApiReturn::error('已点赞');
        }
        if(in_array($uid,$step_ids)){
            return ApiReturn::error('已灭灯');
        }

        if(empty($taskInfo['step_ids'])){
            $where['step_ids'] = $uid;
        }else{
            $where['step_ids'] = $taskInfo['step_ids'].','.$uid;
        }
        $where['step_num'] = $taskInfo['step_num'] + 1;

        //更新任务的灭灯信息
        Db::startTrans();
        try{
            //更新任务点赞信息
            $res = (new TaskLogic())->save($where,['id' => $id]);
            if($where['step_num'] == 1 && $taskInfo['point_num'] >=1 && $taskInfo['level'] != 2){
                //去除任务语音动态
                (new DynamicLogic())->save(['status' => 2],['t_id'=>$taskInfo['id']]);

            }

            Db::commit();
            return ApiReturn::success('灭灯成功');

        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error($e->getMessage());
        }



    }


    /**
     * 个人收到的任务评价列表
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assess_list($data)
    {
        $field = ['uid','pageNo','pagesize','home_page'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $pageNo = $data['pageNo'];
        $pagesize = $data['pagesize'];
        $home_page = $data['home_page'];
        $query = Db::table('task')->alias('t')
            ->join('user u','t.uid=u.id')
            ->where(['t.p_id'=>$uid,'t.type' => 1,'t.status'=>1,'t.assess' =>array('gt',0)])
            ->field('t.id,t.assess,t.assess_content,t.home_page,u.username,u.logo');
        if($home_page == 1){
            $query->where('t.home_page',$home_page);
        }

        $offset = $this->getOffset($pageNo,$pagesize);
        $list = $query->limit($offset,$pagesize)->select();
        return ApiReturn::success('success',$list);


    }

    /**
     * 添加标签时生成一级任务或者升级时匹配三个用户
     * @param $uid
     * @param $flag_user
     * @param $flag_like
     * @param $type
     * @return bool
     */
    public function create_task($userInfo,$flag_user,$flag_like,$type)
    {
        //$flag_user,$flag_like为数组格式
        //为用户生成一个任务，并生成一条佣金记录，待任务完成发放
        //用户添加完喜欢的标签，生成对应的任务
        //给用户生成一个任务，给任务分配三个游客用户
        //根据当前用户选择的标签，推送与之匹配的三个会员用户
        $uid = $userInfo['id'];
        $p_id = $userInfo['p_id'];
        Db::startTrans();
        try{
            $visitor_ids = [];
            $forbid_uid = [$uid,$p_id];
            foreach ($flag_like as $k => $v){
//                dump($forbid_uid);
                $map = [
                    'fu.f_id' => $v,
                    'fu.uid' => array('not in',$forbid_uid),
                    'u.status' => 1
                ];
                $order = 'fu.num asc';
//            $res = (new FlagUserLogic())->getInfo($map,$order,'uid');
                //查找出每个标签下的用户
                $res = Db::table('flag_user')->alias('fu')
                    ->join('user u','u.id = fu.uid')
                    ->where($map)
                    ->order($order)
                    ->field('fu.f_id,fu.uid,fu.num,u.status,u.flag_like')
                    ->select();
//                dump($res);
                if($res){
                    //判断这些用户选择的喜欢的标签是否与当前用户的自身标签相吻合
                    foreach ($res as $kk => $vv){
                        //匹配人员喜欢的标签  $vv['flag_like']
                        $flag_like_visitor = explode(',',$vv['flag_like']);
                        $arr = array_intersect($flag_like_visitor,$flag_user);
                        if($arr){
                            array_push($visitor_ids,$vv['uid']);//将该用户id添加到分配的游客ids数组中
                            array_push($forbid_uid,$vv['uid']);//将该用户id添加到下个标签筛选跳过的用户ids数组中
                            break;
                        }
                    }

                    $count = count($visitor_ids);
                    if($count <= $k){
                        //说明此次标签没有匹配到合适的人选，则分配第一个（因为他被匹配的次数最少）
                        array_push($visitor_ids,$res[0]['uid']);//将该用户id添加到分配的游客ids数组中
                        array_push($forbid_uid,$res[0]['uid']);//将该用户id添加到下个标签筛选跳过的用户ids数组中
                    }
//                    dump($visitor_ids);
                    //将推送次数+1
                    (new FlagUserLogic())->setInc(['f_id' => $v,'uid' => $visitor_ids[$k]],'num',1);

                }else{
                    //该标签下没有找到可以匹配的人,从数据库随机选择一个人匹配
//                $max = Db::table('user')->max('id');
//                $min = Db::table('user')->min('id');
//                $rand = rand($min,$max);
//                dump($rand);
//                dump($forbid_uid);
                    $order = 'num asc';
                    $where = [
                        'status' => 1,
                        'has_flag' => 1,
                        'id' => array('not in' , $forbid_uid),
                    ];
                    $user = Db::table('user')->where($where)->order($order)->find();

                    array_push($visitor_ids,$user['id']);//将该用户id添加到分配的游客ids数组中
                    array_push($forbid_uid,$user['id']);//将该用户id添加到下个标签筛选跳过的用户ids数组中
                    //将随机推送次数+1
                    (new UserLogic())->setInc(['id' => $user['id']],'num',1);
//dump($visitor_ids);
                }

            }



            $visitor_ids_str = implode(',',$visitor_ids);
//                        $visitor_ids_str = '42,43,44';

            if($type == 1){
                //生成任务和佣金记录
                //获取用户的上级信息
                $userInfo = (new UserLogic())->getInfo(['id' => $uid]);
                $parentInfo = (new UserLogic())->getInfo(['id' => $userInfo['p_id']]);
                //判断该用户与该上级是否已经生成任务
                $res = (new TaskLogic())->getInfo(['p_id'=>$parentInfo['id'],'uid'=>$userInfo['id']],false,'id');
                if($res){
                    return false;
                }
                $data = [
                    'p_id' => $parentInfo['id'],
                    'uid' => $userInfo['id'],
                    'user_level' => $userInfo['level'],
                    'visitor_ids' => $visitor_ids_str,
                    'one' => $visitor_ids[0],
                    'two' => $visitor_ids[1],
                    'three' => $visitor_ids[2],
                ];
                $t_id = (new TaskLogic())->getInsertId($data);

                //上级佣金需要等帮助用户完成任务，并获得中评以上评价发放
                //生成一条佣金记录，待完成任务发放
                (new CommissionLogic())->save([
                    't_id' => $t_id,
                    'uid' => $parentInfo['id'],
                    'money' => LEVEL_ONE_MONEY,
                    'level' => 1,
                    'q_uid' => $uid
                ]);
            }else{
                //只推荐三个人
                //获取用户的上级信息
                $data = [
                    'uid' => $uid,
                    'visitor_ids' => $visitor_ids_str,
                    'user_level' => $userInfo['level'],
                    'one' => $visitor_ids[0],
                    'two' => $visitor_ids[1],
                    'three' => $visitor_ids[2],
                    'level' => 0
                ];
                (new TaskLogic())->getInsertId($data);

            }
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
//            return $e->getMessage();
            return false;
        }


    }

    /**
     * 创建二级任务
     * @param $uid
     * @param $grandId
     * @param $visitors
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create_task_two($uid,$grandId,$visitors)
    {
        //$flag_user,$flag_like为数组格式
        //为用户生成一个任务，并生成一条佣金记录，待任务完成发放
        //用户添加完喜欢的标签，生成对应的任务
        //给用户生成一个任务，给任务分配三个游客用户
        //根据当前用户选择的标签，推送与之匹配的三个会员用户
        $userInfo = (new UserLogic())->getInfo(['id' =>$uid]);
        $flag_like = explode(',',$userInfo['flag_like']);
        $flag_user = explode(',',$userInfo['flag_user']);

        //获取用户喜欢的标签性别
//        $like_sex = (new FlagLikeLogic())->getInfo(['f_id' => array('in',$flag_like)]);
//        $like_sex = $like_sex['sex'];
        $visitor_ids = [];
        $visitors_str = $grandId . ','. $uid . ','. $visitors;
        $forbid_uid = explode(',',$visitors_str);

        foreach ($flag_like as $k => $v){
            $map = [
                'fu.f_id' => $v,
                'fu.uid' => array('not in',$forbid_uid),
                'u.status' => 1,
            ];
            $order = 'fu.num asc';
//            $res = (new FlagUserLogic())->getInfo($map,$order,'uid');
            //查找出每个标签下的用户
            $res = Db::table('flag_user')->alias('fu')
                ->join('user u','u.id = fu.uid')
                ->where($map)
                ->order($order)
                ->field('fu.f_id,fu.uid,fu.num,u.status,u.flag_like')
                ->select();
            if($res){
                //判断这些用户选择的喜欢的标签是否与当前用户的自身标签相吻合
                foreach ($res as $kk => $vv){
                    //匹配人员喜欢的标签  $vv['flag_like']
                    $flag_like_visitor = explode(',',$vv['flag_like']);
                    $arr = array_intersect($flag_like_visitor,$flag_user);
                    if($arr){
                        array_push($visitor_ids,$vv['uid']);//将该用户id添加到分配的游客ids数组中
                        array_push($forbid_uid,$vv['uid']);//将该用户id添加到下个标签筛选跳过的用户ids数组中
                        break;
                    }
                }

                $count = count($visitor_ids);
                if($count <= $k){
                    //说明此次标签没有匹配到合适的人选，则分配第一个（因为他被匹配的次数最少）
                    array_push($visitor_ids,$res[0]['uid']);//将该用户id添加到分配的游客ids数组中
                    array_push($forbid_uid,$res[0]['uid']);//将该用户id添加到下个标签筛选跳过的用户ids数组中
                }
                //将推送次数+1
                (new FlagUserLogic())->setInc(['f_id' => $v,'uid' => $visitor_ids[$k]],'num',1);

            }else{
                //该标签下没有找到可以匹配的人,从数据库随机选择一个人匹配
//                $max = Db::table('user')->max('id');
//                $min = Db::table('user')->min('id');
//                $rand = ceil(rand($min,$max) / 2);
                $order = 'num asc';
                $where = [
                    'status' => 1,
                    'has_flag' => 1,
                    'id' => array('not in' , $forbid_uid),
                ];
                $user = Db::table('user')->where($where)->order($order)->find();
                array_push($visitor_ids,$user['id']);//将该用户id添加到分配的游客ids数组中
                array_push($forbid_uid,$user['id']);//将该用户id添加到下个标签筛选跳过的用户ids数组中

                //将随机推送次数+1
                (new UserLogic())->setInc(['id' => $user['id']],'num',1);

            }

        }


        $visitor_ids_str = implode(',',$visitor_ids);
//                        $visitor_ids_str = '42,43,44';
        //生成任务和佣金记录
        //获取用户的上级信息

        $res = (new TaskLogic())->getInfo(['p_id'=>$grandId,'uid'=>$userInfo['id']],false,'id');
        if($res){
            return ApiReturn::error('请勿重复提交');
        }
        $data = [
            'p_id' => $grandId,
            'uid' => $userInfo['id'],
            'user_level' => $userInfo['level'],
            'visitor_ids' => $visitor_ids_str,
            'level' => 2,
            'one' => $visitor_ids[0],
            'two' => $visitor_ids[1],
            'three' => $visitor_ids[2],
        ];
        $t_id = (new TaskLogic())->getInsertId($data);

        //上级佣金需要等帮助用户完成任务，并获得中评以上评价发放
        //生成一条佣金记录，待完成任务发放
        (new CommissionLogic())->save([
            't_id' => $t_id,
            'uid' => $grandId,
            'money' => LEVEL_TWO_MONEY,
            'level' => 2,
            'q_uid' => $uid
        ]);



    }

    /**
     * 评价放置首页
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function home_page($data)
    {
        $field = ['id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $res = (new TaskLogic())->save(['home_page' => 1],['id' => $data['id']]);
        if($res){
            return ApiReturn::success('success');
        }else{
            return ApiReturn::error('fail');
        }




    }

    /**
     * 评价移除首页
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function home_remove($data)
    {
        $field = ['id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $res = (new TaskLogic())->save(['home_page' => 0],['id' => $data['id']]);
        if($res){
            return ApiReturn::success('success');
        }else{
            return ApiReturn::error('fail');
        }




    }


    /**
     * 发送语音
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_voice($data)
    {
        $field = ['voice','length','t_id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $voice = $data['voice'];
        $length = $data['length'];
        $t_id = $data['t_id'];
        $res = (new TaskLogic())->save(['voice' => $voice,'length' => $length],['id' => $t_id]);
        if($res){
            return ApiReturn::success('上传成功');
        }else{
            return ApiReturn::error('上传失败');
        }

    }



}

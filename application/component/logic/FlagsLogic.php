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
use app\component\model\FlagsModel;
use app\component\model\UserModel;
use think\Db;

class FlagsLogic  extends BaseLogic
{
    /**
     * @param $pagesize
     * @param $title
     * @return array
     * @throws \think\exception\DbException
     */
    public function getFlagsListss($pagesize,$title)
    {
        $order = 'sort desc,create_time desc';
        $query = Db::table('flags')
            ->where(['level' => 1,'status' => 1])
            ->order($order);
        if(!empty($title)){
            $query->where('title','like',"%$title%");
        }
        $lists = $query->fetchSql(false)->paginate($pagesize,false,['query'=>request()->param()])
            ->each(function($info){
                $count = (new FlagUserLogic())->count(['f_id'=>$info['id']]);
                $info['count'] = $count;
                return $info;
            });


        $count = Db::table('flags')->count();
        return ['list'=>$lists,'count'=>$count];
    }

    public function detail($id)
    {
        $config = config('paginate');
        $list = Db::table('flag_user')->alias('f')
            ->join('user u','f.uid=u.id')
            ->where('f.f_id',$id)
            ->field('u.username,u.id')
            ->fetchSql(false)->paginate($config);
        return ['page' => $list->render(),'lists'=>$list];
    }

    /**
     * 添加
     * @param $data
     * @return string
     */
    public function addInfo($data)
    {
        $data =  $data['data'];
        unset($data['file']);
        $res = (new FlagsLogic())->getInfo($data);
        if($res){
            return '该标签已存在';
        }
        $model = new FlagsModel();
        $res = $model->save($data);
        if($res){
            return '添加成功';
        }else{
            return '添加失败';
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function updateInfo($data)
    {
        $data = $data['data'];
        $id = $data['id'];
        unset($data['id']);
        unset($data['file']);
        $model = new FlagsModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }

    /**
     * @param $where
     * @param string $order
     * @return array
     * @throws \think\exception\DbException
     */
    public function queryLists($where,$order = '')
    {
        $query = Db::table('flags')->where($where);
        if(!empty($order)){
            $query->order($order);
        }
        $config = config('paginate');
        $lists = $query->fetchSql(false)->paginate($config);
        return ['page' => $lists->render(),'lists'=>$lists];
    }

    /**
     * @param $data
     * @return false|int
     */
    public function setSorts($data)
    {
        $sorts = $data['sorts'];
        $ids = $data['ids'];
        foreach ($sorts as $k=>$v){
            $model = new FlagsModel();
            $res = $model->save(['sort'=>$v],['id'=>$ids[$k]]);
        }
        return $res;
    }

    /**
     * 自定义标签
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function defined($data)
    {
        $field = ['title','uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }

        $userInfo = (new UserLogic())->getInfo(['id' => $data['uid']]);
        if(empty($userInfo)){
            return ApiReturn::error('用户信息错误');
        }
        //自定义一个标签
        $data['type'] = 1;
        $data['level'] = 0;
        $data['create_time'] = time();
        $res = (new FlagsLogic())->getInsertId($data);

        if($res){
            return ApiReturn::success('添加成功',$res);
        }else{
            return ApiReturn::error('添加失败');
        }

    }


    /**
     * 全部一级标签
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function flag_list_one()
    {
        $list = Db::table('flags')
            ->where(['level'=>1,'status' => 1,'type'=>0])
            ->order('sort deac , create_time asc')
            ->select();
        return ApiReturn::success('success',$list);
    }

    /**
     * 某个一级标签下的二级标签
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function flag_list_two($data)
    {
        $field = ['id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }

        $list = Db::table('flags')->where(['level'=>2,'p_id'=>$data['id'],'status' => 1,'type'=>0])->select();
        return ApiReturn::success('success',$list);
    }



    /**
     * 添加标签（包括自身的和喜欢的）
     * 生成任务或匹配三个人
     * @param $data
     * @return array|bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_all_flags($data)
    {
        $field = ['uid','flag_user','flag_like','user_sex'];//flags形式  '1,2,3';
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        $flag_user = $data['flag_user'];
        $flag_like = $data['flag_like'];
        $user_sex = $data['user_sex'];
//        $like_sex = $data['like_sex'];
        Db::startTrans();
        try{
            //判断用户是否禁用
            $userInfo = (new UserLogic())->getInfo(['id' => $uid]);
            if($userInfo && $userInfo['status'] == 0){
                return ApiReturn::error('该用户已被禁用');
            }
            //根据用户当前等级，判断用户选择完标签是否需要生成任务
            //如果用户等级为1，则根据用户选择标签匹配对应的三个人，并生成一个任务
            //如果用户等级大于1，则根据用户选择标签匹配对应的三个人，不用生成任务
            if($userInfo['level'] == 1){
                //将用户选择的标签添加到用户表中
                (new UserLogic())->save(['flag_like' => $flag_like,'flag_user' => $flag_user,'has_flag' => 1],['id' => $uid]);
                //添加到flag_user和flag_like表中
                $flag_likes = explode(',',$flag_like);
                $flag_users = explode(',',$flag_user);
                foreach ($flag_likes as $v){
                    (new FlagLikeLogic())->save(['uid' => $uid,'f_id' => $v]);
                }
                foreach ($flag_users as $v){
                    (new FlagUserLogic())->save(['uid' => $uid,'f_id' => $v,'sex' => $user_sex]);
                }
                //根据用户选择标签，生成匹配任务
//                $res = (new TaskLogic())->create_task($uid,$flag_users,$flag_likes,1);
                $res = (new TaskLogic())->create_task($userInfo,$flag_users,$flag_likes,1);

            }elseif($userInfo['level'] == 0){
                //用户为普通用户
                return ApiReturn::error('用户等级太低');
            }elseif ($userInfo['level'] == 2){
                //将用户选择的标签添加到用户表中
                (new UserLogic())->save(['flag_like' => $flag_like,'flag_user' => $flag_user,'has_flag' => 1],['id' => $uid]);
                //添加到flag_user和flag_like表中
                $flag_likes = explode(',',$flag_like);
                $flag_users = explode(',',$flag_user);
                foreach ($flag_likes as $v){
                    (new FlagLikeLogic())->save(['uid' => $uid,'f_id' => $v]);
                }
                foreach ($flag_users as $v){
                    (new FlagUserLogic())->save(['uid' => $uid,'f_id' => $v,'sex' => $user_sex]);
                }
                //判断是否为免费用户，不生成任务
                if($userInfo['invite_code'] != 'Mrtengda'){
                    //根据用户选择标签，生成匹配任务
                    $res = (new TaskLogic())->create_task($userInfo,$flag_users,$flag_likes,1);
//                    $res = (new TaskLogic())->create_task($uid,$flag_users,$flag_likes,1);
                }

            }elseif ($userInfo['level'] == 6 && $userInfo['type'] == 1){
                //将用户选择的标签添加到用户表中
                (new UserLogic())->save(['flag_like' => $flag_like,'flag_user' => $flag_user,'has_flag' => 1],['id' => $uid]);
                //添加到flag_user和flag_like表中
                $flag_likes = explode(',',$flag_like);
                $flag_users = explode(',',$flag_user);
                foreach ($flag_likes as $v){
                    (new FlagLikeLogic())->save(['uid' => $uid,'f_id' => $v]);
                }
                foreach ($flag_users as $v){
                    (new FlagUserLogic())->save(['uid' => $uid,'f_id' => $v,'sex' => $user_sex]);
                }
                //判断是否为免费用户，不生成任务
//                if($userInfo['invite_code'] != 'Mrtengda'){
//                    //根据用户选择标签，生成匹配任务
//                    $res = (new TaskLogic())->create_task($userInfo,$flag_users,$flag_likes,1);
////                    $res = (new TaskLogic())->create_task($uid,$flag_users,$flag_likes,1);
//                }

            }else{
                //判断此次选择的标签与之前的是否有重复
                $flags_like_now = explode(',',$flag_like);
                //检验该用户选择的标签是否已经添加
                $map = [
                    'uid' => $uid,
                    'f_id' => array('in',$flags_like_now)
                ];
                $res = (new FlagLikeLogic())->getLists($map);
                if($res){
                    return ApiReturn::error('喜欢的标签重复，请重选');
                }
                $flags_user_now = explode(',',$flag_user);
                //检验该用户选择的标签是否已经添加
                $map = [
                    'uid' => $uid,
                    'f_id' => array('in',$flags_user_now)
                ];
                $res = (new FlagUserLogic())->getLists($map);
                if($res){
                    return ApiReturn::error('自身的标签重复，请重选');
                }

                //此次为新增标签
                //添加到flag_user和flag_like表中
                $flag_likes = explode(',',$flag_like);
                $flag_users = explode(',',$flag_user);

                foreach ($flag_likes as $v){
                    (new FlagLikeLogic())->save(['uid' => $uid,'f_id' => $v]);
                }
                foreach ($flag_users as $v){
                    (new FlagUserLogic())->save(['uid' => $uid,'f_id' => $v,'sex'=>$user_sex]);
                }
                $flag_user = $userInfo['flag_user'] . ',' . $flag_user;
                $flag_like = $userInfo['flag_like'] . ',' . $flag_like;
                //将用户选择的标签添加到用户表中
                $res = (new UserLogic())->save(['flag_like' => $flag_like,'flag_user' => $flag_user,'has_flag' => 1],['id' => $uid]);

                //推荐三个人
                $res = (new TaskLogic())->create_task($userInfo,$flag_users,$flag_likes,2);
            }
            $userInfo = (new UserLogic())->getInfo(['id'=>$uid]);
            Db::commit();
            return ApiReturn::success('成功',$userInfo);
        }catch (\Exception $e){
            Db::rollback();
//            return ApiReturn::error($e->getMessage());
            return ApiReturn::error('失败，请重试');
        }




    }


    /**
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_flag($data)
    {
        $field = ['uid','title','pageNo','pagesize'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $title = $data['title'];
        $title = urldecode($title);
        $uid= $data['uid'];
        $pageNo = $data['pageNo'];
        $pagesize = $data['pagesize'];
        $offset = $this->getOffset($pageNo,$pagesize);

        //根据搜索用户去判断搜索条件
        $userInfo = (new UserLogic())->getInfo(['id'=>$uid],false,'id,username,level,province,city,type,status');
//        if($userInfo['level'] < 6){
//            return ApiReturn::error('等级太低');
//        }
        $where = [];
//        if($userInfo['level'] == 6){
//            if(!empty($userInfo['city'])){
//                $where = ['u.city' => $userInfo['city']];
//            }
//        }

        $map = [
            'f.title' => array('like',"%$title%"),
            'u.status' => 1,
            'u.id' => array('neq',$uid),
        ];
        $query = Db::table('flag_user')->alias('fu')
            ->join('flags f','f.id=fu.f_id')
            ->join('user u','fu.uid=u.id')
            ->where($map)
//            ->where('f.title','like',"%$title%")
//            ->where('u.status',1)
            ->order('fu.num asc,u.num asc,fu.update_time desc')
            ->field('u.id,u.username,u.logo,u.flag_user,u.city,u.province,u.status,u.level,f.title');
        if(!empty($where)){
            $query->where($where);
        }

           $query = $query ->limit($offset,$pagesize)->select();
        $list = [];
        foreach ($query as $k=>$v){
            $list[$k] = $v;
//            dump($list[$k]);
            $flags_arr = explode(',',$v['flag_user']);
            $title = [];
            foreach ($flags_arr as $kk => $vv){
                $flagInfo = (new FlagsLogic())->getInfo(['id'=>$vv],false,'title');
                array_push($title,$flagInfo['title']);
            }
            $list[$k]['flag_title'] = $title;

        }
        return ApiReturn::success('success',$list);

    }

    /**
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ajax_flag($data)
    {
        $field = ['title'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $title = $data['title'];

        $query = Db::table('flags')
            ->where('title','like',"%$title%")
            ->select();
        return $query;

    }



}

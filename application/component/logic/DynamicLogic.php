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
use app\component\model\DynamicModel;
use app\component\model\UserModel;
use think\Db;

class DynamicLogic  extends BaseLogic
{
    public function getDynamicByUid($id,$pagesize)
    {
        $query = Db::table('dynamic')
            ->where(['is_complain'=>1,'uid'=>$id]);
        $list = $query->fetchSql(false)->paginate($pagesize,false,['query'=>request()->param()])
            ->each(function($info){
                if($info['type'] == 2 || $info['type'] == 4){
                    $img_ids = explode(',',$info['imgs']);
                    $imgs = Db::table('img')->where('id','in',$img_ids)->field('id,url')->select();
                    $info['imgs'] = $imgs;
                }
                return $info;
            });
        return ['list'=>$list,'page'=>$list->render()];
    }

    /**
     * 添加动态，评论动态
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addDynamic($data)
    {
        //cate参数必传
        if(!isset($data['cate'])){
            return ApiReturn::error('缺少参数');
        }

        if($data['cate'] == 1){
            //发表动态
            $field = ['uid','type'];
        }elseif ($data['cate'] == 2){
            //对动态进行评论
            $field = ['uid','type','p_id'];
        }else{
            //对评论进行评论
            $field = ['uid','type','p_id','c_uid'];
        }

        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        //判断用户是否可用发布动态
        $userInfo = (new UserLogic())->getInfo(['id'=>$data['uid']],false,'id,status,complain_num,level');
        if($userInfo['level'] < 1 || $userInfo['status'] != 1 || $userInfo['complain_num'] >=3){
            return ApiReturn::error('该用户不可发布动态');
        }
        //内容和图片必须有一个
        if(empty($data['content']) && empty($data['imgs'])){
            return ApiReturn::error('动态内容不能为空');
        }
//        $res = (new DynamicLogic())->save($data);
        $id = (new DynamicLogic())->getInsertId($data);
        if($res){
            return ApiReturn::success('添加成功',$id);
        }else{
            return ApiReturn::error('添加失败');
        }

    }

    /**
     * 动态列表
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function listDynamic($data)
    {
        $field = ['pageNo','pagesize'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $order = 'd.stick desc,d.sort desc,d.id desc';
        $map = [
            'd.p_id' => 0,
            'd.status' => 1,
            'd.is_complain'=>0,
        ];
        $query = Db::table('dynamic')->alias('d')
            ->join('user u','d.uid=u.id')
            ->where($map)
            ->field('d.id,d.uid,d.p_id,d.c_uid,d.t_id,d.content,d.voice,d.length,d.imgs,d.type,d.cate,d.stick,d.point_ids,d.status,d.create_time,u.username,u.logo,u.level,u.flag_like,u.flag_user,u.is_hide')
            ->order($order);
        $offset = $this->getOffset($data['pageNo'],$data['pagesize']);
        $list = $query->limit($offset,$data['pagesize'])->select();


        //获取动态下的评论
        $lists = [];
        foreach ($list as $k =>$v){
            $lists[$k]['dynamic'] = $v;

            $lists[$k]['dynamic']['imgs'] = [];
            //判断是否有图片
            if($v['type'] == 2 || $v['type'] == 4){
                $img_ids = explode(',',$v['imgs']);
                $imgs = Db::table('img')->where('id','in',$img_ids)->field('id,url')->select();
                $lists[$k]['dynamic']['imgs'] = $imgs;

            }

            //评论
            $where = ['d.status' => 1,'d.p_id' => $v['id']];
            $comments = Db::table('dynamic')->alias('d')
                ->join('user u','d.uid = u.id')
                ->where($where)
                ->field('d.id,d.uid,d.p_id,d.c_uid,d.content,d.voice,d.length,d.imgs,d.type,d.cate,d.stick,d.point_ids,d.status,d.create_time,u.username,u.flag_like,u.flag_user,u.is_hide')
                ->select();

            $lists[$k]['comments'] = $comments;
            foreach ($comments as $kk => $vv){
                $lists[$k]['comments'][$kk]['c_username'] = '';
                if($vv['cate'] == 3){
                    $user = (new UserLogic())->getInfo(['id'=>$vv['c_uid']],false,'username');
                    $lists[$k]['comments'][$kk]['c_username'] = $user['username'];
                }
            }

            //该动态有点赞，获取点赞的用户信息
            $point_info = [];
            if(!empty($v['point_ids'])){
                $point_ids = explode(',',$v['point_ids']);
                foreach ($point_ids as $kk => $vv){
                    $userInfo = (new UserLogic())->getInfo(['id'=>$vv],false,'id,username,level,flag_like,flag_user,is_hide');
                    array_push($point_info,$userInfo);
                }
            }
            $lists[$k]['point_info'] = $point_info;


        }
        return $lists;
    }


    /**
     * @param $where
     * @param $order
     * @param $pagesize
     * @return array
     * @throws \think\exception\DbException
     */
    public function getDynamicListss($where,$order,$pagesize)
    {
        $query = Db::table('dynamic')->alias('d')
            ->join('user u','d.uid=u.id')
            ->where($where)
            ->field('d.*,u.username')
            ->order($order);
        $list = $query->fetchSql(false)->paginate($pagesize,false,['query'=>request()->param()])
            ->each(function($info){
                if($info['type'] == 2 || $info['type'] == 4){
                    $img_ids = explode(',',$info['imgs']);
                    $imgs = Db::table('img')->where('id','in',$img_ids)->field('id,url')->select();
                    $info['imgs'] = $imgs;
                }
                return $info;
            });


        $count = Db::table('dynamic')->alias('d')
            ->join('user u','d.uid = u.id')
            ->where($where)
            ->count();
        return ['list'=>$list,'count'=>$count];
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
            $model = new DynamicModel();
            $res = $model->save(['sort'=>$v],['id'=>$ids[$k]]);
        }
        return $res;
    }

    /**
     * @param $stick
     * @param $id
     * @return false|int
     */
    public function setStick($stick,$id)
    {
        if($stick == '1'){
            $stick = 0;
        }else{
            $stick = 1;
        }
        $model = new DynamicModel();
        $res = $model->save(['stick'=>$stick],['id'=>$id]);
        return $res;
    }

    /**
     * 动态详情
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($id)
    {
        $info = Db::table('dynamic')->alias('d')
            ->join('user u','d.uid=u.id')
            ->where('d.id',$id)
            ->field('d.id,d.uid,d.p_id,d.c_uid,d.content,d.voice,d.length,d.imgs,d.type,d.cate,d.stick,d.point_ids,d.status,d.create_time,u.username,u.logo,u.level')
            ->find();
        //获取对应的点评论
        //判断是否有图片
        if($info['type'] == 2 || $info['type'] == 4){
            $img_ids = explode(',',$info['imgs']);
            $imgs = Db::table('img')->where('id','in',$img_ids)->field('id,url')->select();
            $info['imgs'] = $imgs;

        }

        //评论
        $where = ['d.status' => 1,'d.p_id' => $info['id']];
        $comments = Db::table('dynamic')->alias('d')
            ->join('user u','d.uid = u.id')
            ->where($where)
            ->field('d.id,d.uid,d.p_id,d.c_uid,d.content,d.voice,d.length,d.imgs,d.type,d.cate,d.stick,d.point_ids,d.status,d.create_time,u.username')
            ->select();

        $info['comments'] = $comments;
        foreach ($comments as $k => $v){
            $info['comments'][$k]['c_username'] = '';
            if($v['cate'] == 3){
                $user = (new UserLogic())->getInfo(['id'=>$v['c_uid']],false,'username');
                $info['comments'][$k]['c_username'] = $user['username'];
            }
        }

        //该动态有点赞，获取点赞的用户信息
        $point_info = [];
        if(!empty($info['point_ids'])){
            $point_ids = explode(',',$info['point_ids']);
            foreach ($point_ids as $v){
                $userInfo = (new UserLogic())->getInfo(['id'=>$v],false,'id,username,level');
                array_push($point_info,$userInfo);
            }
        }
        $info['point_info'] = $point_info;

        return $info;
    }


    /**
     * 动态点赞
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function point($data)
    {
        $field = ['uid','id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        //判断该用户是否已经点赞过该动态
        //获取该动态的点赞人信息
        $dynamic = (new DynamicLogic())->getInfo(['id'=>$data['id']],false,'point_ids');
        $point_ids = explode(',',$dynamic['point_ids']);

        if(in_array($data['uid'],$point_ids)){
            return ApiReturn::error('已点赞');
        }
        //将该用户添加到点赞用户里面
        if(empty($dynamic['point_ids'])){
            $dynamic['point_ids'] = $data['uid'];
        }else{
            $dynamic['point_ids'] = $dynamic['point_ids'].','.$data['uid'];
        }
        $res = (new DynamicLogic())->save(['point_ids'=>$dynamic['point_ids']],['id'=>$data['id']]);
        if($res){
            return ApiReturn::success('点赞成功');
        }else{
            return ApiReturn::error('点赞失败');
        }

    }


    /**
     * 取消动态点赞
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel($data)
    {
        $field = ['uid','id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        //获取该动态的点赞人信息
        $dynamic = (new DynamicLogic())->getInfo(['id'=>$data['id']],false,'point_ids');
        $point_ids = explode(',',$dynamic['point_ids']);
        if(!in_array($data['uid'],$point_ids)){
            return ApiReturn::error('该用户未点赞');
        }
        foreach ($point_ids as $k => $v){
            if($data['uid'] == $v){
                unset($point_ids[$k]);
            }
        }
        if(empty($point_ids)){
            $point_ids = 0;
        }else{
            $point_ids = implode(',',$point_ids);
        }
        $res = (new DynamicLogic())->save(['point_ids'=>$point_ids],['id'=>$data['id']]);
        if($res){
            return ApiReturn::success('取消成功');
        }else{
            return ApiReturn::error('取消失败');
        }

    }


    /**
     * 个人动态列表
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myDynamicList($data)
    {
        $field = ['uid','pageNo','pagesize'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }

        $order = 'd.stick desc,d.sort desc,d.id desc';
        $map = [
            'd.p_id' => 0,
            'd.status' => array('egt',0),
            'd.uid' => $data['uid']
        ];
        $query = Db::table('dynamic')->alias('d')
            ->join('user u','d.uid=u.id')
            ->where($map)
            ->field('d.id,d.uid,d.p_id,d.c_uid,d.content,d.voice,d.length,d.imgs,d.type,d.cate,d.stick,d.point_ids,d.status,d.is_complain,d.create_time,u.username,u.logo,u.level')
            ->order($order);
        $offset = $this->getOffset($data['pageNo'],$data['pagesize']);
        $list = $query->limit($offset,$data['pagesize'])->select();


        //获取动态下的评论
        $lists = [];
        foreach ($list as $k =>$v){
            $lists[$k]['dynamic'] = $v;

            $lists[$k]['dynamic']['imgs'] = [];
            //判断是否有图片
            if($v['type'] == 2 || $v['type'] == 4){
                $img_ids = explode(',',$v['imgs']);
                $imgs = Db::table('img')->where('id','in',$img_ids)->field('id,url')->select();
                $lists[$k]['dynamic']['imgs'] = $imgs;

            }

            //评论
            $where = ['d.status' => 1,'d.p_id' => $v['id']];
            $comments = Db::table('dynamic')->alias('d')
                ->join('user u','d.uid = u.id')
                ->where($where)
                ->field('d.id,d.uid,d.p_id,d.c_uid,d.content,d.voice,d.length,d.imgs,d.type,d.cate,d.stick,d.point_ids,d.status,d.create_time,u.username')
                ->select();

            $lists[$k]['comments'] = $comments;
            foreach ($comments as $kk => $vv){
                $lists[$k]['comments'][$kk]['c_username'] = '';
                if($vv['cate'] == 3){
                    $user = (new UserLogic())->getInfo(['id'=>$vv['c_uid']],false,'username');
                    $lists[$k]['comments'][$kk]['c_username'] = $user['username'];
                }
            }

            //该动态有点赞，获取点赞的用户信息
            $point_info = [];
            if(!empty($v['point_ids'])){
                $point_ids = explode(',',$v['point_ids']);
                foreach ($point_ids as $kk => $vv){
                    $userInfo = (new UserLogic())->getInfo(['id'=>$vv],false,'id,username,level');
                    array_push($point_info,$userInfo);
                }
            }
            $lists[$k]['point_info'] = $point_info;


        }
        return $lists;


    }


    /**
     * 删除动态
     * @param $data
     * @return array|int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delDynamic($data)
    {
        $field = ['uid','id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $id = $data['id'];
        $uid = $data['uid'];
        $info = (new DynamicLogic())->getInfo(['id'=>$id,'uid' => $uid]);
        if(empty($info)){
            return ApiReturn::error('请勿删除他人动态');
        }
        $res = Db::table('dynamic')
            ->where('id',$id)
            ->whereOr('p_id' ,$id)
            ->delete();
        if($res){
            return ApiReturn::success('删除成功');
        }else{
            return ApiReturn::error('删除失败');
        }


    }











}

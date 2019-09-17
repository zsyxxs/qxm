<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:06
 */

namespace app\admin\controller;

use app\component\logic\DynamicLogic;
use app\component\logic\FlagLikeLogic;
use app\component\logic\FlagsLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\PictureLogic;
use app\component\logic\UserLogic;
use app\component\model\UserModel;
use think\Controller;
use think\Db;
use think\Request;

class User extends BaseAdmin
{
    /**
     * @return \think\response\View
     */
    public function index()
    {
        $username = $this->_param('username','');
        $sex = $this->_param('sex','');


        $pagesize = 20;
        //根据用户信息，获取对应的权限列表
        $logic = new UserLogic();
        $userLists = $logic->getUserListss($pagesize,$username,$sex);
//        dump($userLists);

        $count = $userLists['count'];
        $page = $userLists['list']->render();
        $this->assign('UserLists',$userLists['list']);
        $this->assign('username',$username);
        $this->assign('sex',$sex);
        $this->assign('count',$count);
        $this->assign('page',$page);
        return view();
    }

    public function rank()
    {

        $start = $this->_param('start',date('Y-m-d',mktime(0,0,0,date('m'),date('d')-7,date('y'))));
        $end = $this->_param('end',date('Y-m-d',time()));

        $list = (new UserLogic())->rank($start,$end);

        $this->assign('list',$list);
        $this->assign('start',$start);
        $this->assign('end',$end);
        return view();
    }

    public function detail()
    {
        $id = $this->_param('id');
        $pagesize = 20;
        //获取用户被投诉下架的动态列表
        $list = (new DynamicLogic())->getDynamicByUid($id,$pagesize);

        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        return view();

    }


    /**
     * @param Request $request
     * @return array|\think\response\View
     */
    public function add(Request $request){

        if($request->isAjax()){
            $data = input();
            $logic = new UserLogic();
            $res = $logic->addUser($data['data']);
            return ['status' => $res];

        }
        return view();
    }

    /**
     * @param Request $request
     * @return array|\think\response\View
     */
    public function edit(Request $request){
        $id = input('id','');

        $userInfo = (new UserLogic())->getInfo(['id'=>$id]);
        $this->assign('userInfo',$userInfo);


        if($request->isAjax()){
            $data = input();
            $logic = new UserLogic();
            $res = $logic->saveInfo($data);
            return ['status' => $res];
        }

        return view();
    }

    /**
     * 单个删除
     * @param Request $request
     * @return array
     */
    public function del(Request $request)
    {
        if($request->isAjax()){
            $id = input('id','');
            $res = (new UserModel())->where('id',$id)->delete();
            if($res){
                return ['msg' => '删除成功'];
            }else{
                return ['msg' => '删除失败'];
            }
        }
    }

    /**
     * 批量删出
     * @param Request $request
     * @return array
     */
    public function delall(Request $request){
        if($request->isAjax()){
            $data = input();
            $count = count($data['id']);
            $res = [];
            for($i=0;$i<$count;$i++){
                $res[] = (new UserModel())->where('id',$data['id'][$i])->delete();
            }
            if($res){
                return ['status' => '删除成功'];
            }else{
                return ['status' => '删除失败'];
            }

        }
    }


    /**
     * 用户详情
     * @return \think\response\View
     */
    public function information(){
        $id = $this->_param('id','');
        $userInfo = (new UserLogic())->getInfo(['id' => $id]);

        $flag_user = explode(',',$userInfo['flag_user']);
        $user_flag = (new FlagsLogic())->getLists(['id'=>array('in',$flag_user)],false,'title');
//        $user_flag = implode(',',$user_flag);
        $this->assign('user_flag',$user_flag);

        $flag_like = explode(',',$userInfo['flag_like']);
        $like_flag = (new FlagsLogic())->getLists(['id'=>array('in',$flag_like)],false,'title');
//        $like_flag = implode(',',$like_flag);
        $this->assign('like_flag',$like_flag);


        $this->assign('userInfo',$userInfo);
        $parentInfo = (new UserLogic())->getInfo(['id' => $userInfo['p_id']]);
        $this->assign('parentInfo',$parentInfo);
        return view();

    }

    /**
     * 设置用户状态
     * @param Request $request
     * @return array
     */
    public function setStatus(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new UserLogic())->setStatus($data['status'],$data['id']);
            return ['status' =>$res];
        }
    }




}
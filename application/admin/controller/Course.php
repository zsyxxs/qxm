<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:06
 */

namespace app\admin\controller;

use app\component\logic\CourseLogic;
use app\component\logic\FlagsLogic;
use app\component\logic\FlagUserLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\PictureLogic;
use app\component\logic\UserLogic;
use app\component\model\CourseModel;
use app\component\model\FlagsModel;
use app\component\model\UserModel;
use think\Controller;
use think\Db;
use think\Request;

class Course extends BaseAdmin
{
    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $title = $this->_param('title','');
        $type = $this->_param('type','');
        $this->assign('type',$type);
        $pagesize = 20;
        //获取所有一级标签列表
        $logic = new CourseLogic();
        $courseLists = $logic->getCourseListss($pagesize,$title,$type);


        $count = $courseLists['count'];
        $page = $courseLists['list']->render();
        $this->assign('courseLists',$courseLists['list']);
        $this->assign('title',$title);
        $this->assign('count',$count);
        $this->assign('page',$page);
        return view();
    }


    /**
     * @param Request $request
     * @return array|\think\response\View
     */
    public function add(Request $request){

        if ($request->isAjax()) {
            $data = input();
            $data['data']['content'] = $data['contents'];
            $logic = new CourseLogic();
            $result = $logic->add($data['data']);
            return ['status' => $result];
        }

        return view();
    }

    /**
     * @param Request $request
     * @return array|\think\response\View
     */
    public function edit(Request $request){
        $id = input('id','');

        $info = (new CourseLogic())->getInfo(['id'=>$id]);
        $this->assign('info',$info);


        if($request->isAjax()){
            $data = input();
            $data['data']['content'] = $data['contents'];
            $logic = new CourseLogic();
            $result = $logic->edit($data['data']);
            return ['status' => $result];
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
            $res = (new CourseModel())->where('id',$id)->delete();
            if($res){
                return ['msg' => '删除成功'];
            }else{
                return ['msg' => '删除失败'];
            }
        }
    }


    /**
     * 设置标签排序
     * @param Request $request
     * @return array
     */
    public function setSorts(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new CourseLogic())->setSorts($data);
            return ['status' =>$res];
        }
    }









}
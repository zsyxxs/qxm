<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:06
 */

namespace app\admin\controller;


use app\component\logic\TaskLogic;
use app\component\logic\UserLogic;
use think\Request;

class Task extends BaseAdmin
{
    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $status = $this->_param('status','0');

        $this->assign('status',$status);
        $pagesize = 20;
        $order = 't.id desc';

        //根据获取任务列表
        $logic = new TaskLogic();
        $taskists = $logic->getUserListss($order,$pagesize,$status);
        $count = $taskists['count'];
        $page = $taskists['list']->render();
        $this->assign('UserLists',$taskists['list']);
        $this->assign('count',$count);
        $this->assign('page',$page);
        return view();
    }





    public function information(){
        $id = $this->_param('id','');
        //任务详情
        $taskInfo = (new TaskLogic())->getTaskDetails($id);
        $this->assign('taskInfo',$taskInfo);

        //游客
        $visitor = (new TaskLogic())->getVisitor($taskInfo);
        $this->assign('visitor',$visitor);

        //点赞人
        $point = (new TaskLogic())->getPoint($taskInfo);
        $this->assign('point',$point);
        return view();

    }




}
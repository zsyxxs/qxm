<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:06
 */

namespace app\admin\controller;


use app\component\logic\DynamicLogic;
use app\component\logic\TaskLogic;
use app\component\logic\UserLogic;
use app\component\model\DynamicModel;
use think\Request;

class Dynamic extends BaseAdmin
{
    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $pagesize = 20;
        $order = 'd.stick desc,d.sort desc,d.id desc';
        $where = [
            'd.status' => 1,
            'd.cate' => 1
        ];
        //根据获取任务列表
        $logic = new DynamicLogic();
        $taskists = $logic->getDynamicListss($where,$order,$pagesize);
        $count = $taskists['count'];
        $page = $taskists['list']->render();
        $this->assign('UserLists',$taskists['list']);
        $this->assign('count',$count);
        $this->assign('page',$page);

        return view();
    }

    public function complain()
    {
        $pagesize = 20;
        $order = 'd.stick desc,d.sort desc,d.id desc';
        $where = [
            'd.status' => 0,
            'd.cate' => 1,
            'd.is_complain' => 1
        ];
        //根据获取任务列表
        $logic = new DynamicLogic();
        $taskists = $logic->getDynamicListss($where,$order,$pagesize);
        $count = $taskists['count'];
        $page = $taskists['list']->render();
        $this->assign('UserLists',$taskists['list']);
        $this->assign('count',$count);
        $this->assign('page',$page);
        return view();
    }


    /**
     * 设置排序
     * @param Request $request
     * @return array
     */
    public function setSorts(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new DynamicLogic())->setSorts($data);
            return ['status' =>$res];
        }
    }

    /**
     * 设置置顶状态
     * @param Request $request
     * @return array
     */
    public function setStick(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new DynamicLogic())->setStick($data['stick'],$data['id']);
            return ['status' =>$res];
        }
    }

    /**
     * 单个删除,删除动态和动态下的评论
     * @param Request $request
     * @return array
     */
    public function del(Request $request)
    {
        if($request->isAjax()){
            $id = input('id','');
            $res = (new DynamicModel())->where('id',$id)->whereOr('p_id',$id)->delete();
            if($res){
                return ['msg' => '删除成功'];
            }else{
                return ['msg' => '删除失败'];
            }
        }
    }


    public function information(){
        $id = $this->_param('id','');
        //获取动态详情
        $detail = (new DynamicLogic())->detail($id);
//        dump($detail);
        $this->assign('detail',$detail);
        return view();

    }




}
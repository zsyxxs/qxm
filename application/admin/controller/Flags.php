<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:06
 */

namespace app\admin\controller;

use app\component\logic\FlagsLogic;
use app\component\logic\FlagUserLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\PictureLogic;
use app\component\logic\UserLogic;
use app\component\model\FlagsModel;
use app\component\model\UserModel;
use think\Controller;
use think\Db;
use think\Request;

class Flags extends BaseAdmin
{
    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $title = $this->_param('title','');


        $pagesize = 20;
        //获取所有一级标签列表
        $logic = new FlagsLogic();
        $flagsLists = $logic->getFlagsListss($pagesize,$title);
//        dump($flagsLists);

        $count = $flagsLists['count'];
        $page = $flagsLists['list']->render();
        $this->assign('flagsLists',$flagsLists['list']);
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

        if($request->isAjax()){
            $data = input();
            $logic = new FlagsLogic();
            $res = $logic->addInfo($data);
            return ['status' => $res];

        }
        //获取可供选择的上级分类（一级和二级）
        $flags = (new FlagsLogic())->queryPage(['status' => 1,'level' => array('lt',3)]);
        $flags = getTree($flags);
        $this->assign('flags',$flags);

        return view();
    }

    /**
     * @param Request $request
     * @return array|\think\response\View
     */
    public function edit(Request $request){
        $id = input('id','');

        $flagsInfo = (new FlagsLogic())->getInfo(['id'=>$id]);
        $this->assign('flagsInfo',$flagsInfo);

        //获取可供选择的上级分类
        $flags = (new FlagsLogic())->queryPage(['level' => array('lt',3)]);
        $flags = getTree($flags);
        $this->assign('flags',$flags);

        if($request->isAjax()){
            $data = input();
            $logic = new FlagsLogic();
            $res = $logic->updateInfo($data);
            return ['status' => $res];
        }

        return view();
    }

    public function detail()
    {
        $id = $this->_param('id','');
        $userList = (new FlagsLogic())->detail($id);
        $this->assign('page',$userList['page']);
        $this->assign('lists',$userList['lists']);

        return view();
    }

    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function son()
    {
        $id = $this->_param('id','');
        $title = $this->_param('title','');
        $where = ['p_id'=>$id,'status'=>1];
        $order = 'sort desc,update_time desc';
        $list = (new FlagsLogic())->queryLists($where,$order);
        $this->assign('list',$list['lists']);
        $this->assign('page',$list['page']);
        $this->assign('title',$title);
        return view();
    }

    /**
     * @return \think\response\View
     */
    public function grandSon()
    {
        $id = $this->_param('id','');
        $title = $this->_param('title','');
        $where = ['p_id'=>$id,'status'=>1];
        $order = 'sort desc,update_time desc';
        $list = (new FlagsLogic())->queryLists($where,$order);
        $this->assign('list',$list['lists']);
        $this->assign('page',$list['page']);
        $this->assign('title',$title);
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
            $res = (new FlagsModel())->where('id',$id)->delete();
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
            $res = (new FlagsLogic())->setSorts($data);
            return ['status' =>$res];
        }
    }

    /**
     * 三级标签列表
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function lists()
    {
        $where = ['level'=>3,'status'=>1];
        $order = 'p_id ase,update_time desc';
        $list = (new FlagsLogic())->queryLists($where,$order);
        $lists = [];
        foreach ($list['lists'] as $k => $v)
        {
            //统计该标签下的人数
            $map = ['status' => 1,'f_id' => $v['id']];
            $count = (new FlagUserLogic())->count($map,'uid');
            //获取对应的上级
            $father = (new FlagsLogic())->getInfo(['id' => $v['p_id']]);
            $grand = (new FlagsLogic())->getInfo(['id' => $father['p_id']]);
            $v['count'] = $count;
            $v['father'] = $father['title'];
            $v['grand'] = $grand['title'];
            array_push($lists,$v);
        }

        $this->assign('list',$lists);
        $this->assign('page',$list['page']);

        return view();
    }

    /**
     * 自定义标签列表
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function defined()
    {
        $where = ['type' => 1];
        $order = 'create_time desc';
        $list = (new FlagsLogic())->queryLists($where,$order);
        $this->assign('list',$list['lists']);
        $this->assign('page',$list['page']);
        return view();
    }






}
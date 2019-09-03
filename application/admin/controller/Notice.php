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
use app\component\logic\NoticeLogic;
use app\component\logic\PictureLogic;
use app\component\logic\UserLogic;
use app\component\model\FlagsModel;
use app\component\model\NoticeModel;
use app\component\model\UserModel;
use think\Controller;
use think\Db;
use think\Request;

class Notice extends BaseAdmin
{
    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $title = $this->_param('content','');


        $pagesize = 20;
        $logic = new NoticeLogic();
        $noticeLists = $logic->getNoticeListss($pagesize,$title);

        $count = $noticeLists['count'];
        $page = $noticeLists['list']->render();
        $this->assign('noticeLists',$noticeLists['list']);
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
            $logic = new NoticeLogic();
            $res = $logic->addInfo($data);
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

        $noticeInfo = (new NoticeLogic())->getInfo(['id'=>$id]);
        $this->assign('noticeInfo',$noticeInfo);


        if($request->isAjax()){
            $data = input();
            $logic = new NoticeLogic();
            $res = $logic->updateInfo($data);
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
            $res = (new NoticeModel())->where('id',$id)->delete();
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








}
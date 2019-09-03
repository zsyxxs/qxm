<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:06
 */

namespace app\admin\controller;


use app\component\logic\ComplainReasonLogic;
use app\component\model\ComplainReasonModel;
use think\Request;

class ComplainReason extends BaseAdmin
{
    /**
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //根据获取任务列表
        $map = ['status' => 1];
        $list = (new ComplainReasonLogic())->queryPageHtml($map);

        $this->assign('list',$list['list']);
        $this->assign('count',$list['count']);
        $this->assign('page',$list['page']);
        return view();
    }

    public function add(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new ComplainReasonLogic())->addInfo($data);
            return ['status'=>$res];
        }
        //获取可供选择的上级权限
        return view();
    }

    public function edit(Request $request){
        $id = input('id','');

        $Info = (new ComplainReasonLogic())->getInfo(['id'=>$id]);
        $this->assign('info',$Info);


        if($request->isAjax()){
            $data = input();
            $logic = new ComplainReasonLogic();
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
            $res = (new ComplainReasonModel())->where('id',$id)->delete();
            if($res){
                return ['msg' => '删除成功'];
            }else{
                return ['msg' => '删除失败'];
            }
        }
    }





}
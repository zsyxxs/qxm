<?php
namespace app\admin\controller;




use app\component\logic\BannersLogic;
use app\component\logic\ProductLogic;
use think\Request;

class Banners extends BaseAdmin
{
   public function index()
   {
       $position = $this->_param('position',1);
       $list = (new BannersLogic())->getBannerLists($position);
       $this->assign('position',$position);
       $this->assign('page',$list['page']);
       $this->assign('lists',$list['lists']);
       return view();
   }

   public function add(Request $request)
   {
       if($request->isAjax()){
           $data = input();
           $res = (new BannersLogic())->addInfo($data);
           return ['status'=>$res];
       }
       return view();
   }

    public function edit(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new BannersLogic())->updateInfo($data);
            return ['status'=>$res];
        }
        $id = $this->_param('id','');
        $info = (new BannersLogic())->getInfoById($id);
        $this->assign('info',$info);
        return view();
    }

   public function del(Request $request)
   {
       $id = $this->_param('id','');
       $res = (new BannersLogic())->del($id);
       return ['status' => $res];

   }



}
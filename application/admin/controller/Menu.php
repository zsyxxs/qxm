<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/8
 * Time: 14:08
 */
namespace app\admin\controller;

use app\component\logic\MenuLogic;
use app\component\model\MenuModel;
use think\Request;

class Menu extends BaseAdmin
{
    /**
     * 菜单列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $lists = (new MenuLogic())->getMenuLists();
        $this->assign('lists',$lists);
        return view();
    }

    /**
     * 添加菜单
     * @param Request $request
     * @return array|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new MenuLogic())->saveOradd($data);
            return ['status'=>$res];
        }
        //获取可供选择的上级权限
        $where = ['level'=>array('lt',4)];
        $p_menus = (new MenuLogic())->getMenuLists($where);
//        dump($p_menus);
        $this->assign('p_menus',$p_menus);
        return view();
    }

    public function edit(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new MenuLogic())->updateInfo($data);
            return ['status'=>$res];
        }
        $id = $this->_param('id','');
        $info = (new MenuLogic())->getInfoById($id);
        $this->assign('info',$info);
        //获取可供选择的上级权限
        $where = ['level'=>array('lt',3)];
        $p_menus = (new MenuLogic())->getMenuLists($where);
//        dump($p_menus);
        $this->assign('p_menus',$p_menus);
        return view();
    }

    public function del(Request $request)
    {
        if($request->isAjax()){
            $id = input('id','');
            $res = (new MenuLogic())->del($id);
            return ['msg' => $res];
        }
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/8
 * Time: 14:08
 */
namespace app\admin\controller;

use app\component\logic\RoleLogic;
use app\component\model\RoleModel;
use think\Request;

class Role extends BaseAdmin
{
    public function index()
    {
        $lists = (new RoleLogic())->getAllRoles();
        $this->assign('page',$lists['page']);
        $this->assign('lists',$lists['lists']);
        return view();
    }

    /**
     * 新增
     * @param Request $request
     * @return array|\think\response\View
     */
    public function add(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new RoleLogic())->addOrSave($data);
            return ['status' => $res];
        }
        return view();
    }

    public function edit(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $res = (new RoleLogic())->updateInfo($data);
            return ['status' => $res];
        }
        $id = input('id');
        //获取当前角色信息
        $roleInfo = (new RoleLogic())->getRoleInfoByid($id);
        $this->assign('roleInfo',$roleInfo);
        return view();
    }

    /**
     * @param Request $request
     * @return array|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function distribute(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $logic = new RoleLogic();
            $res = $logic->setMenuIds($data);
            if($res){
                return ['status' => 1];
            }else{
                return ['status' => 0];
            }

        }else{
            $role_id = input('id','');
            //获取所有菜单列表和当前角色所拥有的可视化菜单ids
            $logic = new RoleLogic();
            $menus = $logic->getMenusAndIds($role_id);
            $this->assign('menus',$menus['list']);
            $this->assign('roleInfo',$menus['roleInfo']);
            return view();
        }
    }

    public function del(Request $request)
    {
        if($request->isAjax()){
            $id = input('id');
            $res = (new RoleModel())->where('id',$id)->delete();
            if($res){
                return ['msg' => '删除成功'];
            }else{
                return ['msg' => '删除失败'];
            }
        }
    }

}
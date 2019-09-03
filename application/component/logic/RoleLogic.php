<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/9
 * Time: 10:04
 */
namespace app\component\logic;

use app\component\model\RoleModel;
use think\Db;

class RoleLogic
{
    /**
     * 获取角色的可视化菜单列表
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenusAndIds($id)
    {
        $roleInfo = Db::table('role')->where('id',$id)->find();
        //根据角色拥有的菜单ids获取对应的菜单列表
//        $menu_ids = explode(',',$roleInfo['menu_ids']);
        $menuinfos = Db::table('menu')->where('status',ENABLE)->select();

        $subMenu = function ($parentId, $menus) {
            $list = [];
            foreach ($menus as $v) {
                if ($v['p_id'] == $parentId)
                    $list[] = $v;
            }
            return $list;
        };
        $list = $subMenu(0, $menuinfos);
        foreach ($list as &$v) {
            $v['children'] = $subMenu($v['id'], $menuinfos);
            foreach ($v['children'] as &$v2) {
                $v2['children'] = $subMenu($v2['id'], $menuinfos);
                foreach($v2['children'] as &$v3){
                    $v3['children'] = $subMenu($v3['id'],$menuinfos);
                }
            }
        }


        return ['list' =>$list,'roleInfo'=>$roleInfo];
    }

    /**
     * 分配菜单ids
     * @param $data
     * @return false|int
     */
    public function setMenuIds($data){
        $arr = [];
        foreach ($data['data'] as $k=>$v){
            $arr[] = $v['value'];
        }
        $menus_ids = implode(',',$arr);
        $model = new RoleModel();
        $res = $model->save([
            'menu_ids' => $menus_ids
        ],['id'=>$data['role_id']]);
        return $res;
    }

    /**
     * 添加manager获取全部的可供选择的角色信息
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRoles($level = '')
    {
        $query = Db::table('role');
        if(!empty($level)){
            $query = $query->where('level','>=',$level);
        }
        $roles = $query->select();
        return $roles;
    }


    /**
     * 角色列表
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllRoles()
    {
        $pagesize = 20;
        $lists = Db::table('role')->where('status',ENABLE)->order('level')->paginate($pagesize);
        $page = $lists->render();
        return ['lists' =>$lists,'page'=>$page];
    }


    /**
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRoleInfoByid($id)
    {
        $info = Db::table('role')->where('id',$id)->find();
        return $info;
    }

    public function addOrSave($data)
    {
        $data = $data['data'];
        $model = new RoleModel();
        $res = $model->get($data);
        if($res){
            return '角色信息重复，请重新添加';
        }else{
            //新增
            $res = $model->save($data);
            if($res){
                return '添加成功';
            }else{
                return '添加失败';
            }
        }
    }

    public function updateInfo($data)
    {
        $id = $data['role_id'];
        $data = $data['data'];
        unset($data['role_id']);
        $model = new RoleModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }

}
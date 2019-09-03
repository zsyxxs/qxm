<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/9
 * Time: 10:04
 */
namespace app\component\logic;


use app\component\model\MenuModel;
use think\Db;

class MenuLogic
{
    /**
     * @param string $where
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuLists($where='')
    {
        $lists = Db::table('menu')
               ->where('status',ENABLE)
               ->where($where)
               ->order('sort desc')
               ->select();
        $lists = getTree($lists);
        return $lists;
    }

    public function saveOradd($data)
    {
        $data = $data['data'];
        $pid_level = explode('-',$data['p_id_level']);
        $data['p_id'] = $pid_level[0];
        $data['level'] = $pid_level[1]+1;
        $data['status'] = 1;
        unset($data['p_id_level']);
        $model = new MenuModel();
        $result = $model->get($data);
        if($result){
            //已存在
            return '该菜单已经存在';
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

    /**
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfoById($id)
    {
        $info = Db::table('menu')->where('id',$id)->find();
        return $info;
    }

    public function updateInfo($data)
    {
        $data = $data['data'];
        $id = $data['id'];
        unset($data['id']);
        $pid_level = explode('-',$data['p_id_level']);
        $data['p_id'] = $pid_level[0];
        $data['level'] = $pid_level[1]+1;
        unset($data['p_id_level']);
        $model = new MenuModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }

    public function del($id)
    {
        $model = new MenuModel();
        $res = $model->save(['status'=>DISABLE],['id'=>$id]);
        if($res){
            return '删除成功';
        }else{
            return '删除失败';
        }
    }

}
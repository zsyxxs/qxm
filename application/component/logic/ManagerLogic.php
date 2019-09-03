<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/9
 * Time: 10:04
 */
namespace app\component\logic;

use app\component\model\ManagersModel;
use app\component\model\UserModel;
use think\Db;

class ManagerLogic
{
    public function forget($phone,$pwd)
    {
        $data = ['pwd' => md5($pwd)];
        $model = new ManagersModel();
        $res = $model->save($data,['phone'=>$phone]);
        return $res;
    }

    public function getMgInfoById($id)
    {
        $info = Db::table('managers')->where('id',$id)->find();
        return $info;
    }

    /**
     * 可供选择的上级人员信息
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getParentManagers($role_id='')
    {
        $query = Db::table('managers')->alias('m')
            ->join('role r','m.role_id=r.id')
            ->field('m.id,m.name,m.role_id,r.role_name,r.level')
            ->order('m.role_id');
        if(!empty($role_id)){
            //获取当前角色的等级
            $info = Db::table('role')->where('id',$role_id)->find();
            $level = $info['level'] - 1;
            $query = $query->where('r.level',$level);
        }
        $p_managers = $query->select();
        return $p_managers;
    }


    public function getManagers($where=[]){
        $managers = Db::table('managers')->alias('m')
            ->join('role r','m.role_id=r.id')
            ->field('m.*')
            ->where('r.level','=',4);
        if(!empty($where)){
            $managers = $managers->where($where);
        }
        $managers = $managers->select();
        return $managers;
    }

    /**
     * 获取人员列表
     * @param $pagesize
     * @param $keyWord
     * @return array
     * @throws \think\exception\DbException
     */
    public function getLists($pagesize,$keyWord)
    {
        $query = Db::table('managers')->alias('m')
            ->where('m.name','like', "%$keyWord%")
            ->join('role r','m.role_id=r.id','left')
            ->join('managers mg','m.p_id=mg.id','left');
        $field = 'm.*,r.role_name,mg.name as p_name';
        $query->field($field);
//        $list = $query->select();
        $lists = $query->fetchSql(false)->paginate($pagesize);
        $count = Db::table('managers')->where('status',1)->count();
        $page = $lists->render();
        return ['lists'=>$lists,'count'=>$count,'page'=>$page];
    }

    /**
     * 添加管理员
     * @param $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addManager($data)
    {
        //判断当前手机号是否已经注册
        $res = Db::table('managers')->where('phone',$data['phone'])->find();
        if($res){
            return '已经注册';//已经注册
        }else{
            Db::startTrans();
            try {
                //添加数据到管理员表
                $model = new ManagersModel();
                $data['pwd'] = md5($data['pwd']);
                $res = $model->save($data);
                $mg_id = $model->id;
                //判断是否为业务员
                if($data['role_id'] == 5){
                    //判断当前手机号是否注册
                    $result = Db::table('user')->where('mobile',$data['phone'])->find();
                    $id = $result['id'];
                    $model = new UserModel();
                    if(!$result){
                        //用户表注册一个用户，默认密码123456
                        $res = $model->save([
                            'username' => $data['name'],
                            'password' => md5(123456),
                            'mobile' => $data['phone'],
                            'type' => 2,
                            'mg_id' => $mg_id
                        ]);
                    }else{
                        $type = $result['type'];
                        if($type == 1){
                            $type = 2;
                        }elseif ($type == 3){
                            $type = 4;
                        }
                        $res = $model->save(['type'=>$type],['id'=>$id]);
                    }
                }
                // 提交事务
                Db::commit();
                return '添加成功';
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $e->getMessage();
            }

        }


    }

    /**
     * 验证登录信息
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($data)
    {
        $where = [
            'phone' => $data['phone'],
            'pwd'  => md5($data['pwd']),
            'status' => 1
        ];
        $result = Db::table('managers')->where($where)->find();
        return $result;
    }

    /**
     * 根据用户信息获取可视化菜单列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenu()
    {
        //获取当前登录用户信息
        $mgInfo = session('manager');
        $uid = $mgInfo['id'];
        $res = Db::table('managers')->alias('m')
            ->join('role r','m.role_id=r.id')
            ->where('m.id='.$uid)
            ->find();


        //根据角色拥有的权限ids获取对应的菜单列表
        $menu_ids = explode(',',$res['menu_ids']);
        $menuinfos = Db::table('menu')->where('id','in',$menu_ids)->where(['status' => ENABLE,'type' => 1])->order('sort desc')->select();
        $newMenus = [];

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
            }
        }
        return $list;
    }

    /**
     * 登录用户的信息
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMgInfo()
    {
        $uid = session('manager');
        $info = Db::table('managers')->alias('m')
            ->join('role r','m.role_id=r.id','left')
            ->field('m.*,r.role_name,r.level')
            ->where('m.status',ENABLE)
            ->where('r.status',ENABLE)
            ->where('m.id',$uid['id'])
            ->find();
        return $info;
    }

    public function saveInfo($data)
    {
        $data = $data['data'];
        $id = $data['mg_id'];
        if(array_key_exists('pwd',$data)){
            $data['pwd'] = md5($data['pwd']);
        }
        unset($data['mg_id']);
        $model = new ManagersModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }

    public function getMgBranchInfo($keyword)
    {
        $config = config('paginate');

        $mg_info = session('manager');
        $branch = Db::table('managers')
                ->alias('m')
                ->join('role r','m.role_id = r.id')
                ->where('m.name','like', "%$keyword%")
                ->where('m.p_id',$mg_info['id'])
                ->field('m.*,r.role_name')
                ->paginate($config);
        $count = Db::table('managers')->where('p_id',$mg_info['id'])->count();
        $page = $branch->render();
        return ['branch'=>$branch,'count'=>$count,'page'=>$page];
    }

    public function getIds($mg_id)
    {
        $list = Db::table('managers')->where('p_id',$mg_id)->field('id')->select();
        $ids = [];
        foreach ($list as $k=>$v){
            $ids[] = $v['id'];
        }
        return $ids;
    }


}
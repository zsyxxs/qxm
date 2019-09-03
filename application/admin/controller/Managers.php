<?php
namespace app\admin\controller;
use app\api\controller\UserAccount;
use app\component\logic\ManagerLogic;
use app\component\logic\RoleLogic;
use app\component\logic\SecurityCodeLogic;
use app\component\logic\UserLogic;
use app\component\model\ManagersModel;
use think\Controller;
use think\Db;
use think\Request;

class Managers extends Controller
{
    /**
     * 人员列表
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $keyWord = input('keyword','');
        $pagesize = 20;
        $logic = new ManagerLogic();
        $lists = $logic->getLists($pagesize,$keyWord);
        $this->assign('keyword',$keyWord);
        $this->assign('page',$lists['page']);
        $this->assign('lists',$lists['lists']);
        $this->assign('count',$lists['count']);
        return view();
    }

    /**
     * 添加用户
     * @param Request $request
     * @return \think\response\View|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if($request->isAjax()){
            $data = input();
            $logic = new ManagerLogic();
            $res = $logic->addManager($data['data']);
            return ['status' => $res];

        }
        //获取当前用户的信息
        $logic = new ManagerLogic();
        $mgInfo = $logic->getMgInfo();
        //获取比当前登录人员权限等级低的全部角色信息
        $roles = (new RoleLogic())->getRoles($mgInfo['level']);
        $this->assign('roles',$roles);

        return view();
    }

    public function getParent()
    {
        $data = input();
        $role_id = $data['role_id'];
        $parent = (new ManagerLogic())->getParentManagers($role_id);
        return ['data'=>$parent];
    }

    public function edit(Request $request)
    {
        $flag = input('flag','');//有值表示个人信息中修改
        if($request->isAjax()){
            $data = input();
            $logic = new ManagerLogic();
            $res = $logic->saveInfo($data);
            return ['status' => $res];
        }
        $id = input('id','');

        $this->assign('flag',$flag);
        if(empty($id)){
            //个人信息中心修改
            //获取当前用户的信息
            $logic = new ManagerLogic();
            $mgInfo = $logic->getMgInfo();
        }else{
            //人员列表编辑
            //获取当前用户的信息
            $logic = new ManagerLogic();
            $mgInfo = $logic->getMgInfoById($id);
        }

        $this->assign('mgInfo',$mgInfo);
        //获取当前用户角色信息
        $roleInfo = (new RoleLogic())->getRoleInfoByid($mgInfo['role_id']);
        $this->assign('roleInfo',$roleInfo);

        //获取当前用户的信息
        $logic = new ManagerLogic();
        $mgInfo = $logic->getMgInfo();
        //获取比当前登录人员权限等级低的全部角色信息
        $roles = (new RoleLogic())->getRoles($mgInfo['level']);
        $this->assign('roles',$roles);

//        $roles = (new RoleLogic())->getRoles();
//        $this->assign('roles',$roles);
        //获取比当前人员的角色等级高出一级的上级信息
//        dump($roleInfo);
        $p_managers = (new ManagerLogic())->getParentManagers($roleInfo['id']);
        $this->assign('p_managers',$p_managers);
        return view();
    }

    public function del(Request $request)
    {
        if($request->isAjax()){
            $id = input('id','');
            $phone = Db::table('managers')->where('id',$id)->value('phone');
            $res = (new ManagersModel())->where('id',$id)->delete();

            $result =  (new UserLogic())->editMgId($id,$phone);

            if($res || $result['status'] == 1){
                return ['status' => '删除成功'];
            }else{
                return ['status' => '删除失败'];
            }
        }
    }

    public function branch(Request $request)
    {
        $keyword = input('keyword','');
        //获取当前登录用户的下属人员信息
        $branch = (new ManagerLogic())->getMgBranchInfo($keyword);
//        dump($branch);
        $this->assign('branch',$branch);
        $this->assign('keyword',$keyword);

        return view();
    }

    /**
     * 后台登录
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(Request $request)
    {
        $msg = '';//错误提示信息
        if($request->isPost()){
            $data = input();
            //判断用户登录信息是否正确
            $logic = new ManagerLogic();
            $res = $logic->getInfo($data);
            if($res){
                session('manager',$res);
                $this->redirect('index/index');
            }else{
                $msg = '用户名或密码错误';
            }

        }
        $this->assign('msg',$msg);
        return view();


    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session('manager',null);
        $this->redirect('login');
    }

    /**
     *忘记密码
     * @param Request $request
     * @return \think\response\View
     */
    public function forget(Request $request)
    {
        $msg = '';//错误提示信息
        if($request->isPost()){
            $data = input();
            //验证手机号和验证码
            $check = (new UserAccount())->checkCode($data['phone'],$data['code']);
            if($check['status'] ==1){
                $msg = '验证码已过期';
            }elseif ($check['status'] ==2){
                $msg = '验证码不正确';
            }else{
                //更改新密码
                $res = (new ManagerLogic())->forget($data['phone'],$data['pwd']);
                if($res){
                    $this->redirect('login');
                }else{
                    $msg = '重置密码失败';
                }
            }

        }
        $apiUrl = config('webUrl.apiUrl');
        $this->assign('msg',$msg);
        $this->assign('apiUrl',$apiUrl);
        return view();
    }


}
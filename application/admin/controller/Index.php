<?php
namespace app\admin\controller;


use app\component\logic\CommissionLogic;
use app\component\logic\ManagerLogic;
use app\component\logic\OrderLogic;
use app\component\logic\ProjectLogic;
use app\component\logic\QxCommissionLogic;
use app\component\logic\TaskLogic;
use app\component\logic\UserLogic;
use app\component\logic\VoiceCommissionLogic;
use think\Db;

class Index extends BaseAdmin
{
    //统计
    public function welcome()
    {
        $logic = new ManagerLogic();
        $mgInfo = $logic->getMgInfo();
        $this->assign('mgInfo',$mgInfo);
        $this->assign('time',time());

        //统计付费会员总数
        $total_fee_num = (new UserLogic())->count(['level' => array('egt',1)]);
        $total_fee_num = Db::table('user')
            ->where('level','egt',1)
            ->where('invite_code','neq','Mrtengda')
            ->where('type',0)
            ->count('*');
//        dump($total_fee_num);
        $this->assign('total_fee_num',$total_fee_num);

        //统计免费会员总数
        $total_free_user = (new UserLogic())->count(['invite_code'=>'Mrtengda']);
        $this->assign('total_free_user', $total_free_user);

        //统计昨日新增付费会员
        $yesterday_begin = mktime(0,0,0,date('m'),date('d') - 1, date('y'));
        $yesterday_end = mktime(23,59,59,date('m'),date('d') - 1 ,date('y'));
        $yesterday_fee_num = Db::table('user')->alias('u')
                            ->join('order o','u.id = o.uid')
                            ->where('o.status',1)
                            ->where('o.update_time','between',[$yesterday_begin,$yesterday_end])
                            ->count();
//        dump($yesterday_fee_num);
        $this->assign('yesterday_fee_num',$yesterday_fee_num);

        //注册未付费用户
        $total_free_num = (new UserLogic())->count(['level' => 0,'has_flag' => 1]);
//        dump($total_free_num);
        $this->assign('total_free_num',$total_free_num);

        //未完成任务数
        $unfinish_task = (new TaskLogic())->count(['assess'=>0,'type'=>1]);
//        dump($unfinish_task);
        $this->assign('unfinish_task',$unfinish_task);

        //任务总差评数
        $total_bad_task = (new TaskLogic())->count(['assess'=>1]);
//        dump($total_bad_task);
        $this->assign('total_bad_task',$total_bad_task);

        //平台总收入金额，总订单数量
        $total_order_num= (new OrderLogic())->count(['status'=>1]);
        $total_money = (new OrderLogic())->sum(['status'=>1],'money');
//        dump($total_order_num);
//        dump($total_money);
        $this->assign('total_order_num',$total_order_num);
        $this->assign('total_money',$total_money);


        //总的一级红包金额和数量
        $one_level_num = (new CommissionLogic())->count([]);
        $one_level_send_money = (new CommissionLogic())->sum(['status'=>1],'money');
        $one_level_wait_money = (new CommissionLogic())->sum(['status'=>0],'money');
//        dump($one_level_num);
//        dump($one_level_send_money);
//        dump($one_level_wait_money);
        $this->assign('one_level_num',$one_level_num);
        $this->assign('one_level_send_money',$one_level_send_money);
        $this->assign('one_level_wait_money',$one_level_wait_money);




        //总的二级红包数量和金额
        $two_level_num = (new QxCommissionLogic())->count([]);
        $two_level_send_money = (new QxCommissionLogic())->sum(['status'=>1],'money');
        $two_level_wait_money = (new QxCommissionLogic())->sum(['status'=>0],'money');
//        dump($two_level_num);
//        dump($two_level_send_money);
//        dump($two_level_wait_money);
        $this->assign('two_level_num',$two_level_num);
        $this->assign('two_level_send_money',$two_level_send_money);
        $this->assign('two_level_wait_money',$two_level_wait_money);



        //任务语音红包数量和金额
        $task_voice_num = (new VoiceCommissionLogic())->count(['status'=>1,'type'=>1]);
        $task_voice_money = (new VoiceCommissionLogic())->sum(['status'=>1,'type'=>1],'money');

        $this->assign('task_voice_num',$task_voice_num);
        $this->assign('task_voice_money',$task_voice_money);

        //随机语音红包数量和金额
        $rand_voice_num = (new VoiceCommissionLogic())->count(['status'=>1,'type'=>2]);
        $rand_voice_money = (new VoiceCommissionLogic())->sum(['status'=>1,'type'=>2],'money');

        $this->assign('rand_voice_num',$rand_voice_num);
        $this->assign('rand_voice_money',$rand_voice_money);


        return view();
    }

    /**
     * 后台首页
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //根据用户信息，获取对应的权限列表
        $logic = new ManagerLogic();
        $menuLists = $logic->getMenu();
        $this->assign('menuLists',$menuLists);
        $homeUrl = config('webUrl.homeUrl');
        $this->assign('homeUrl',$homeUrl);
        return view();
    }

    public function myself()
    {
        //获取当前用户的信息
        $logic = new ManagerLogic();
        $mgInfo = $logic->getMgInfo();
        $mgInfo['now'] = time();
        $this->assign('mgInfo',$mgInfo);
//        dump($mgInfo);
        return view();
    }



}
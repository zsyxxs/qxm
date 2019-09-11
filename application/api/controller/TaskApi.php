<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/7/2
 * Time: 17:51
 */

namespace app\api\controller;



use app\component\logic\TaskLogic;
use app\component\logic\UserLogic;
use app\component\logic\VoiceCommissionLogic;

class TaskApi extends BaseApi
{

    /**
     * 完成任务
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function finish()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->finish($data);
        return $res;
    }

    /**
     * 评价任务
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assess()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->assess($data);
        return $res;

    }


    /**
     * 任务列表（废弃）
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->lists($data);
        return $res;
    }

    /**d
     * 任务卡列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_list()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->task_list($data);
        return $res;
    }

    /**
     * 点赞任务
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function point_up()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->point_up($data);
        return $res;
    }

    /**
     * 任务灭灯
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function step_down()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->step_down($data);
        return $res;
    }


    /**
     * 个人收到的任务评价列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assess_list()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->assess_list($data);
        return $res;
    }

    /**
     * 评价放置首页
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function home_page()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->home_page($data);
        return $res;
    }

    /**
     * 评价移除首页
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function home_remove()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->home_remove($data);
        return $res;
    }

    /**
     * 获取任务语音红包
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function voice_commission()
    {
        $data = $_REQUEST;
        $res = (new VoiceCommissionLogic())->voice_commission($data);
        return $res;
    }

    /**
     * 听语音获取随机红包
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function random_voice_commission()
    {
        $data = $_REQUEST;
        $res = (new VoiceCommissionLogic)->random_voice_commission($data);
        return $res;
    }

    /**
     * 发送语音
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_voice()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->add_voice($data);
        return $res;
    }

    /**
     * 发送文字
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_text()
    {
        $data = $_REQUEST;
        $res = (new TaskLogic())->add_text($data);
        return $res;
    }





}

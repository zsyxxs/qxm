<?php
/**
 * 微信网页授权类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;




use app\component\logic\CourseLogic;
use app\component\logic\MyCourseLogic;

class CourseApi extends BaseApi
{

    /**
     * 教程列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   public function lists()
   {
       $data = $_REQUEST;
       $res = (new CourseLogic())->lists($data);
       return $res;
   }

    /**
     * 用户自定义教程
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
   public function defined()
   {
       $data = $_REQUEST;
       $res = (new CourseLogic())->defined($data);
       return $res;
   }

    /**
     * 某个任务给用户发送教程
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function send_course()
    {
        $data = $_REQUEST;
        $res = (new MyCourseLogic())->send_course($data);
        return $res;
    }


    /**
     * 某个任务用户收到的教程列表
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_course()
    {
        $data = $_REQUEST;
        $res = (new MyCourseLogic())->get_course($data);
        return $res;
    }

    /**
     * 根据id查询教程详情
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function course_detail()
    {
        $data = $_REQUEST;
        $res = (new CourseLogic())->course_detail($data);
        return $res;
    }

    /**
     * 删除教程
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del_course()
    {
        $data = $_REQUEST;
        $res = (new CourseLogic())->del_course($data);
        return $res;
    }











}
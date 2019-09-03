<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/7/2
 * Time: 17:51
 */

namespace app\api\controller;



use app\component\logic\FlagLikeLogic;
use app\component\logic\FlagsLogic;
use app\component\logic\FlagUserLogic;


class FlagsApi extends BaseApi
{

    /**
     * 添加标签（包括自身的和喜欢的）
     * @return array|bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_all_flags()
    {
        $data = $_REQUEST;
        $res = (new FlagsLogic())->add_all_flags($data);
        return $res;

    }

    /**
     * 禁用用户全部标签
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function forbidden()
    {
        $data = $_REQUEST;
        $res = (new FlagUserLogic())->forbidden($data);
        return $res;
        
    }

    /**
     * 启用用户的全部标签
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function enable()
    {
        $data = $_REQUEST;
        $res = (new FlagUserLogic())->enable($data);
        return $res;
    }

    /**
     * 用户自定义标签
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function defined()
    {
        $data = $_REQUEST;
        $res = (new FlagsLogic())->defined($data);
        return $res;
    }


    /**
     * 一级标签列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function flag_list_one()
    {
        $res = (new FlagsLogic())->flag_list_one();
        return $res;
    }

    /**
     * 某个一级标签下的二级标签
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function flag_list_two()
    {
        $data = $_REQUEST;
        $res = (new FlagsLogic())->flag_list_two($data);
        return $res;
    }


    /**
     * 搜索相似标签
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ajax_flag()
    {
        $data = $_REQUEST;
        $res = (new FlagsLogic())->ajax_flag($data);
        return $res;
    }

    /**
     * 搜索标签
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_flag()
    {
        $data = $_REQUEST;
        $res = (new FlagsLogic())->search_flag($data);
        return $res;
    }







}

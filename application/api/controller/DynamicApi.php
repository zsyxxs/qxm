<?php
/**
 * 微信网页授权类
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/23
 * Time: 9:31
 */

namespace app\api\controller;





use app\api\helper\ApiReturn;
use app\component\logic\DynamicLogic;
use app\component\logic\ImgLogic;

class DynamicApi extends BaseApi
{

    /**
     * 添加动态
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addDynamic()
    {
        $data = $_REQUEST;
        $res = (new DynamicLogic())->addDynamic($data);
        return $res;
    }

    /**
     * 动态列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function listDynamic()
    {
        $data = $_REQUEST;
        $res = (new DynamicLogic())->listDynamic($data);
        return $res;
    }

    /**
     * 动态详情
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail()
    {
        $id = $this->_param('id','');
        $res = (new DynamicLogic())->detail($id);
        return ApiReturn::success('成功',$res);
    }

    /**
     * 动态点赞
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function point()
    {
        $data = $_REQUEST;
        $res = (new DynamicLogic())->point($data);
        return $res;
    }

    /**
     * 取消动态点赞
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel()
    {
        $data = $_REQUEST;
        $res = (new DynamicLogic())->cancel($data);
        return $res;
    }


    /**
     * 个人动态列表
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myDynamicList()
    {
        $data = $_REQUEST;
        $res = (new DynamicLogic())->myDynamicList($data);
        return $res;

    }

    /**
     * 删除动态
     * @return array|int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delDynamic()
    {
        $data = $_REQUEST;
        $res = (new DynamicLogic())->delDynamic($data);
        return $res;
    }

    /**
     * 上传单张动态图
     * @return array
     */
    public function uploadImg()
    {
        $urls = (new Upload())->fileUpload();
        $map = [
            'url' => $urls['data']
        ];
        $id = (new ImgLogic())->getInsertId($map);
        return ApiReturn::success('上传成功',['id' => $id,'url' => $urls['data']]);
    }


    /**
     * 上传动态图片（可批量）
     * @return array
     */
    public function dynaUploadImg()
    {
        $urls = (new Upload())->batchUpload();
        if($urls['code'] == '-1'){
            return $urls;
        }
        $ids = [];
        foreach ($urls['data'] as $k => $v){
            $map = [
                'url' => $v
            ];
            $id = (new ImgLogic())->getInsertId($map);
            if($id){
                array_push($ids,$id);
            }
        }

        return ApiReturn::success('上传成功',$ids);


    }


















}
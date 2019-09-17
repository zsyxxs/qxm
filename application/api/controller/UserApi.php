<?php
/**
 * Created by PhpStorm.
 * User: boye
 * Date: 2019/7/2
 * Time: 17:51
 */

namespace app\api\controller;



use app\api\helper\ApiReturn;
use app\component\logic\ImgLogic;
use app\component\logic\UserLogic;

class UserApi extends BaseApi
{
    /**
     * 更新用户信息
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateInfo()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->updateInfo($data);
        return $res;
    }

    /**
     * ajax验证用户名
     * @return array
     */
    public function check_name()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->check_name($data);
        return $res;
    }

    /**
     * 根据id获取用户信息
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->login($data);
        return $res;
    }

    /**
     * 根据unionid获取用户信息
     * @return array|string
     */
    public function getUserInfoByUnionid()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->getUserInfoByUnionid($data);
        return $res;
    }


    /**
     * 用户下线统计
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function count_user()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->count_user($data);
        return $res;
    }

    /**
     * 绑定邀请码
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_code($data)
    {
        $res = (new UserLogic())->add_code($data);
        return $res;
    }

    /**
     * 用户个人主页
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->getUserInfo($data);
        return $res;
    }

    /**
     * 解除禁言
     */
    public function can_speak(){
        $ids = (new UserLogic())->column('id','ns_endtime<'.time());
        if($ids) (new UserLogic())->save(['ns_endtime'=>0], ['id'=>['in', $ids]]);
    }

    /**
     * 设置隐身
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function set_hide()
    {
        $data = $_REQUEST;
        $res = (new UserLogic())->setHide($data);
        return $res;
    }

    public function uploadUserImg()
    {
//        $uid = $this->_param('uid','');
//        $type = $this->_param('type','3');
//        $length = $this->_param('length','');
////        $img_url = $this->_param('img_url','http://mpian.oss-cn-hangzhou.aliyuncs.com/mingpian/20190129/ab4c87aae498a88afb7637bd957bfe078e8938fc.jpg');
        $img_url = (new Upload())->fileUpload();
//        if(!$img_url){
//            return $this->ajaxReturn('success','请选择正确的文件上传',-1);
//        }
//        $res = (new UserApiHelper())->uploadUserImg($uid,$type,$img_url,$length);
//        $code = $this->getCode($res);

        return $img_url;
    }

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

    public function upload()
    {
        return view();
    }



}

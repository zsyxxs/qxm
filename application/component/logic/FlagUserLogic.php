<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\CashModel;
use app\component\model\DyBannersModel;
use app\component\model\UserModel;
use think\Db;

class FlagUserLogic  extends BaseLogic
{



    /**
     * 禁用用户的全部标签（包括自身的和选择他人的）
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function forbidden($data)
    {
        $field = ['uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        //判断用户是否禁用
        $userInfo = (new UserLogic())->getInfo(['id' => $data['uid']]);
        if($userInfo['status'] == 0){
            return ApiReturn::error('该用户已被禁用');
        }
        //同时禁用该用户的全部标签，包括自身的和选择他人的
        Db::startTrans();
        try{
            //禁用自身标签
            (new FlagUserLogic())->save(['status' => 0],['uid' => $data['uid']]);
            //禁用选择的喜欢的标签
            (new FlagLikeLogic())->save(['status' => 0],['uid' => $data['uid']]);

            Db::commit();
            return ApiReturn::success('禁用成功');

        }catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::error('禁用失败，请重试');
        }



    }


    /**
     * 启用用户的全部标签
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function enable($data)
    {
        $field = ['uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }

        //判断用户是否禁用
        $userInfo = (new UserLogic())->getInfo(['id' => $data['uid']]);
        if($userInfo['status'] == 0){
            return ApiReturn::error('该用户已被禁用');
        }
        //同时启用该用户的全部标签，包括自身的和选择他人的
        Db::startTrans();
        try{
            //启用自身标签
            (new FlagUserLogic())->save(['status' => 1],['uid' => $data['uid']]);
            //启用选择的喜欢的标签
            (new FlagLikeLogic())->save(['status' => 1],['uid' => $data['uid']]);

            Db::commit();
            return ApiReturn::success('启用成功');

        }catch (\Exception $e) {
            Db::rollback();
            return ApiReturn::error('启用失败，请重试');
        }



    }










}

<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;




use app\api\helper\ApiReturn;
use app\component\model\CourseModel;
use think\Db;

class MyCourseLogic  extends BaseLogic
{


    /**
     * 发送教程
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function send_course($data)
    {
        unset($data['s']);
        $field = ['p_id','uid','c_id','t_id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        //判断是否已经发送过该教程
        $res = (new MyCourseLogic())->getInfo($data);
        if($res){
            return ApiReturn::error('请勿重复发送');
        }
        Db::startTrans();
        try{
            //添加发送教程记录
            (new MyCourseLogic())->save($data);
            //更改任务的教程发送状态
            (new TaskLogic())->save(['is_send' => ENABLE],['id' => $data['t_id']]);
            Db::commit();
            return ApiReturn::success('发送成功');

        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('发送失败');
        }




    }


    /**
     * 某个任务用户收到的教程
     * @param $data
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_course($data)
    {
        unset($data['s']);
        $field = ['p_id','uid','t_id','pageNo','pagesize'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }


        $where = [
            'm.p_id' => $data['p_id'],
            'm.uid' => $data['uid'],
            'm.t_id' => $data['t_id'],
            'c.status' => 1
        ];
        $query = Db::table('my_course')->alias('m')
            ->join('course c','c.id=m.c_id')
            ->where($where)
            ->field('c.id,c.author,c.title,m.create_time');
        $offset = $this->getOffset($data['pageNo'],$data['pagesize']);
        $res = $query->limit($offset,$data['pagesize'])->select();
        if(isset($res[0])){
            return ApiReturn::success('success',$res);
        }else{
            return ApiReturn::error('培训老师正在准备教程');
        }

    }







}

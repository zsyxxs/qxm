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
use app\component\model\FlagsModel;
use app\component\model\NoticeModel;
use app\component\model\UserModel;
use think\Db;

class NoticeLogic  extends BaseLogic
{
    /**
     * @param $pagesize
     * @param $title
     * @return array
     * @throws \think\exception\DbException
     */
    public function getNoticeListss($pagesize,$title)
    {
        $order = 'create_time desc';
        $query = Db::table('notice')
            ->where(['status' => 1])
            ->order($order);
        if(!empty($title)){
            $query->where('title','like',"%$title%");
        }
        $lists = $query->fetchSql(false)->paginate($pagesize);

        $count = Db::table('notice')->count();
        return ['list'=>$lists,'count'=>$count];
    }

    /**
     * 添加
     * @param $data
     * @return string
     */
    public function addInfo($data)
    {
        $data =  $data['data'];
        unset($data['file']);
        $res = (new NoticeLogic())->getInfo($data);
        if($res){
            return '该标签已存在';
        }
        $model = new NoticeModel();
        $res = $model->save($data);
        if($res){
            return '添加成功';
        }else{
            return '添加失败';
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function updateInfo($data)
    {
        $data = $data['data'];
        $id = $data['id'];
        unset($data['id']);
        unset($data['file']);
        $model = new NoticeModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }

    /**
     * @param $where
     * @param string $order
     * @return array
     * @throws \think\exception\DbException
     */
    public function queryLists($where,$order = '')
    {
        $query = Db::table('flags')->where($where);
        if(!empty($order)){
            $query->order($order);
        }
        $config = config('paginate');
        $lists = $query->fetchSql(false)->paginate($config);
        return ['page' => $lists->render(),'lists'=>$lists];
    }

    /**
     * @param $data
     * @return false|int
     */
    public function setSorts($data)
    {
        $sorts = $data['sorts'];
        $ids = $data['ids'];
        foreach ($sorts as $k=>$v){
            $model = new FlagsModel();
            $res = $model->save(['sort'=>$v],['id'=>$ids[$k]]);
        }
        return $res;
    }










}

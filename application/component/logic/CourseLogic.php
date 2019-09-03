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
use My\Space\ExceptionNamespaceTest;
use think\Db;

class CourseLogic  extends BaseLogic
{
    /**
     * @param $pagesize
     * @param $title
     * @return array
     * @throws \think\exception\DbException
     */
    public function getCourseListss($pagesize,$title,$type=1)
    {
        $order = 'c.type asc,c.sort desc,c.update_time desc';
        $query = Db::table('course')->alias('c')
            ->join('user u','u.id = c.uid','left');
        if($type == 0){
            $query->where('c.status' , 1);
        }else{
            $query->where(['c.status' => 1,'c.type'=>$type]);
        }

        if(!empty($title)){
            $query->where('title','like',"%$title%");
        }
        $query->field('c.* ,u.username')
            ->order($order);

        $lists = $query->fetchSql(false)->paginate($pagesize);

        $count = Db::table('course')->count();
        return ['list'=>$lists,'count'=>$count];
    }

    /**
     * 添加
     * @param $data
     * @return string
     */
    public function add($data)
    {
        unset($data['file']);
        $model = new CourseModel();
        $res = $model->save($data);
        if($res){
            return '新增成功';
        }else{
            return '增加失败';
        }
    }

    public function edit($data)
    {
        $id = $data['id'];
        unset($data['file']);
        unset($data['id']);
        $model = new CourseModel();
        $res = $model->save($data,['id' => $id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
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
            $model = new CourseModel();
            $res = $model->save(['sort'=>$v],['id'=>$ids[$k]]);
        }
        return $res;
    }


    /**
     * 教程列表
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists($data)
    {
        $field = ['uid','pagesize','pageNo'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $uid = $data['uid'];
        //获取系统教程和用户自定义教程
        $arr = [0,$uid];
        $map = [
            'status' => 1,
            'uid' => array('in',$arr)
        ];
        $order = 'type asc,sort desc,update_time desc';
        $list = (new CourseLogic())->queryPage($map,$order,'id,author,uid,title,voice,content,type,status,create_time',$data['pageNo'],$data['pagesize']);

        return ApiReturn::success('success',$list);

    }


    /**
     * 用户自定义教程
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function defined($data)
    {
        $field = ['title','content','voice','uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $user = (new UserLogic())->getInfo(['id'=>$data['uid']],false,'id,username');
        $data['author'] = $user['username'];
        $data['type'] = 2;
        $res = (new CourseLogic())->save($data);
        if($res){
            return ApiReturn::success('success');
        }else{
            return ApiReturn::error('自定义失败，请重试');
        }


    }


    /**
     * @param $data
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function course_detail($data)
    {
        $field = ['id'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $id = $data['id'];
        $info = (new CourseLogic())->getInfo(['id' => $id],false,'id,author,uid,title,voice,content,create_time');
        return ApiReturn::success('success',$info);

    }

    /**
     * 删除教程
     * @param $data
     * @return array|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del_course($data)
    {
        $field = ['id','uid'];
        $res = $this->checkFields($field,$data);
        if($res !== ENABLE){
            return $res;
        }
        $info = (new CourseLogic())->getInfo(['id'=>$data['id'],'uid'=>$data['uid']],false,'id');
        if(empty($info)){
            return ApiReturn::error('该教程不可删除');
        }
        $res = (new CourseLogic())->delete(['id'=>$data['id'],'uid'=>$data['uid']]);
        if($res){
            return ApiReturn::success('success');
        }else{
            return ApiReturn::error('删除失败');
        }


    }




}

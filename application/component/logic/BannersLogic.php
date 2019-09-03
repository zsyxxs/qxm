<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;


use app\api\helper\ApiReturn;
use app\component\model\BannersModel;
use think\Db;

class BannersLogic  extends BaseLogic
{
    public function getInfoById($id)
    {
        $info = Db::table('banners')->alias('b')
            ->join('picture p','b.img=p.id','left')
            ->where('b.status',ENABLE)
            ->where('b.id',$id)
            ->field('b.*,p.primary_file_uri')
            ->find();
        return $info;
    }

    public function getBannerLists($position)
    {
        $query = Db::table('banners')->alias('b')
            ->join('picture p','b.img=p.id','left')
            ->field('b.*,p.primary_file_uri')
            ->where('status',ENABLE);
        if(!empty($position)){
            $query->where('position',$position);
        }
        $config = config('paginate');
        $lists = $query->fetchSql(false)->paginate($config);
        return ['page' => $lists->render(),'lists'=>$lists];

    }

    /**
     * 添加轮播图
     * @param $data
     * @return string
     */
    public function addInfo($data)
    {
        $data =  $data['data'];
        unset($data['file']);
        $model = new BannersModel();
        $res = $model->save($data);
        if($res){
            return '上传成功';
        }else{
            return '上传失败';
        }
    }

    public function updateInfo($data)
    {
        $data = $data['data'];
        $id = $data['id'];
        unset($data['file']);
        unset($data['id']);
        $model = new BannersModel();
        $res = $model->save($data,['id'=>$id]);
        if($res){
            return '修改成功';
        }else{
            return '修改失败';
        }
    }

    public function del($id)
    {
        $model = new BannersModel();
        $res = $model->save(['status'=>DISABLE],['id'=>$id]);
        if($res){
            return '删除成功';
        }else{
            return '删除失败';
        }
    }

    public function queryBy($position,$pagesize,$pageNo)
    {
        $offset = ($pageNo - 1) > 0 ? ($pageNo - 1) : 0 ;
        $query = Db::table('banners')->alias('b')
            ->join('picture p','b.img=p.id','left')
            ->field('b.id,p.primary_file_uri,b.url,b.url_type')
            ->where('status',1)
            ->where('position',$position)
            ->limit($offset*$pagesize,$pagesize)
            ->select();
        $query = $this->getImgUrl($query);
        return ApiReturn::success('success',$query);
    }

}
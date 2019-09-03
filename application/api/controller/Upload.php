<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/10
 * Time: 15:31
 */

namespace app\api\controller;

use app\api\helper\ApiReturn;
use think\Db;
use think\Image;

class Upload extends BaseApi
{
    //上传文件(单个)
    public function fileUpload()
    {
        $info = Request()->file('file');

        if($info){
            //验证上传文件的合法性
            $allow_type = ['.jpg','.jpeg','.png','.mp4','.mp3','.aac','.m4a'];
            $fileInfo = $info->getInfo();
            $format = strrchr($fileInfo['name'],'.');
            if(!in_array($format,$allow_type)){
                return ApiReturn::error('上传文件格式错误');
            }


//            validate(['size'=>1567118]);
//            $image = \think\Image::open(request()->file('image'));
//            $image->thumb(150, 150,\think\Image::THUMB_FIXED)->save(ROOT_PATH . 'public/thumb/'.md5(time()).'.jpg');


            $newFilePath = 'qxm/'.date("Ymd").'/'.sha1(date('YmdHis',time()).uniqid()).$format;
            $res = (new OssApi())->upload($newFilePath,$fileInfo['tmp_name']);

            return ApiReturn::success('上传成功',$res['info']['url']);

        }else{
            return ApiReturn::error('请选择上传文件');
        }
    }

    /**
     * 批量上传图片
     * @return array|string
     */
    public function batchUpload()
    {
        Db::startTrans();
        try{
            $files = $_FILES['file'];
            $urls = [];
            $arr = [];
            foreach ($files as $k => $info)
            {
                foreach ($info as $kk => $v){
                    $arr[$kk][$k] = $v;
                }
            }
            foreach ($arr as $k => $v){
                $allow_type = ['.jpg','.jpeg','.png','.mp4','.mp3','.aac','.m4a'];
                $format = strrchr($v['name'],'.');
                if(!in_array($format,$allow_type)){
                    return ApiReturn::error('上传文件格式错误');
                }
                $newFilePath = 'qxm/'.date("Ymd").'/'.sha1(date('YmdHis',time()).uniqid()).$format;
                $res = (new OssApi())->upload($newFilePath,$v['tmp_name']);
                array_push($urls,$res['info']['url']);
            }
            Db::commit();
            return ApiReturn::success('success',$urls);
        }catch (\Exception $e){
            Db::rollback();
            return ApiReturn::error('fail');
        }





    }




}
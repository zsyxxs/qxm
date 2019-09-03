<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 14:14
 */

namespace app\admin\controller;


use app\component\logic\PictureLogic;
use think\Request;

class Upload extends BaseAdmin
{
    /**
     * 图片上传接口
     * @return int|string
     */
    public function UploadPicture ()
    {
        //收集上传图片
//        $file = Request()->file('image');
        $file = Request()->file('file');
        $files = $_FILES['file'];
        if($file){
            //验证上传文件的合法性`
            $result = $this->validate(['goods_logo'=>$file],['goods_logo'=>'require|image'],['goods_logo.require'=>'请选择上传文件','goods_logo.image'=>'请选择图片上传']);

            if(true !== $result){
                //上传文件不符合要求 ，输出错误信息并返回上一个页面
                $this->error($result);
            }else{
                //上传文件符合要求
                //将上传文件移动到根目录的uploads目录下
                $info = $file->move(ROOT_PATH.'public'.DS.'uploads/pic');
                if($info){
                    //获取上传文件的信息
                    $primary_name = $info->getSaveName();

                    $primary_name  = str_replace('\\','/',$primary_name);
                    $primary_name_url  = './uploads/pic/'. $primary_name ;
                    $ori_name      = $files['name'];
                    $save_name1    = $info->getFilename();
                    $save_name2    = strpos($save_name1,'.');
                    $save_name     = substr($save_name1,0,$save_name2+1);

                    $size = $files['size'];
                    $url = "#";
                    $md5 = md5($primary_name_url);
                    $sha1 = sha1($primary_name_url);
                    $type = $files['type'];
                    $primary_name1 = $info->getSaveName();
                    $primary_name2 = strpos($primary_name1,'.');
                    $ext  = substr($primary_name1,$primary_name2+1);

                    $data =   [
                        'primary_file_uri'  =>$primary_name_url,
                        'ori_name'          =>$ori_name,
                        'save_name'         =>$save_name,
                        'size'              =>$size,
                        'url'               =>$url,
                        'md5'               =>$md5,
                        'sha1'              =>$sha1,
                        'type'              =>$type,
                        'ext'               =>$ext,
                        'create_time'       =>time(),
                        'update_time'       =>time(),
                    ];

                    $id = (new PictureLogic())->UploadPicture($data);
                    return $id;
                }
            }
        }else{
            //文件上传失败
            $this->error('文件上传失败，请重新上传！');
        }
    }


    public function newUpload()
    {
        $file = Request()->file('file');
        if($file){
            $res = (new \app\api\controller\Upload())->fileUpload($file);
//            $img_id = (new ImgsLogic())->add($res);
            return json(['code'=>1,'url'=>$res['data']]);
        }
    }

    // 编辑器  图片上传接口
    public function upload()
    {
        $file = Request()->file('file');
        if($file){
            $res = (new \app\api\controller\Upload())->fileUpload($file);
            $result = [
                'code' => 0,
                'msg' => '上传成功',
                'data' => [
                    'src' => $res['data'],
                    'title' => ''
                ]
            ];
            return $result;
        }
    }



}

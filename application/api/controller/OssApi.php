<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/10
 * Time: 10:49
 */
namespace app\api\controller;



use app\component\interfaces\oss\api\OssResultApi;

class OssApi extends BaseApi
{
    //创建bucket
    public function createBucket()
    {
       return (new OssResultApi())->createBucket();
    }

    //上传字符串
    public function uploadStr()
    {
        return (new OssResultApi())->uploadStr();
    }

    //上传文件
    public function upload($newFilePath,$saveName)
    {
        return (new OssResultApi())->upload($newFilePath,$saveName);
    }

    //下载文件
    public function downLoad()
    {
        return (new OssResultApi())->downLoad();
    }



}

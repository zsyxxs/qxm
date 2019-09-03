<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/10
 * Time: 11:45
 */
namespace app\component\interfaces\oss\api;
use OSS\Core\OssException;
use OSS\Http\RequestCore;
use OSS\Http\ResponseCore;
use OSS\OssClient;


class OssResultApi extends BaseOss
{
    //创建存储空间
    public function createBucket()
    {
        $bucket = "mingpabs";
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->createBucket($bucket);
        } catch (OssException $e) {
            print $e->getMessage();
        }
    }

    //上传字符串
    public function uploadStr()
    {
        // 存储空间名称
        $bucket= "mpian";
        // 文件名称
        $object = "mingpian";
        // 文件内容
        $content = "Hello OSS";
        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);

            $ossClient->putObject($bucket, $object, $content);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    //上传文件
    public function  upload($newFilePath,$saveName)
    {
        // 存储空间名称
        $bucket= $this->bucket;
        // 文件名称
        $object = $newFilePath;
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
        $filePath = $saveName;

        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res = $ossClient->uploadFile($bucket, $object, $filePath);
            return $res;
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
//        print(__FUNCTION__ . ": OK" . "\n");
    }


    //下载文件
    public function downLoad()
    {
        $bucket= "mpian";
        // object 表示您在下载文件时需要指定的文件名称，如abc/efg/123.jpg。
        $object = "mingpian";
        // 指定文件下载路径。
        $localfile = "40.jpg";
        $options = array(
            OssClient::OSS_FILE_DOWNLOAD => $localfile
        );

        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);

            $ossClient->getObject($bucket, $object, $options);
        } catch(OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK, please check localfile: 'upload-test-object-name.txt'" . "\n");
    }




}
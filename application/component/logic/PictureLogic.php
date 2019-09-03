<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/16
 * Time: 13:44
 */

namespace app\component\logic;

use app\component\model\PictureModel;
use app\component\model\UserModel;
use think\Db;

class PictureLogic  extends BaseLogic
{
    public function UploadPicture($data,$pk = 'id'){
//        $result = (new PictureModel())->data($data)->save();
        $result = Db::name('Picture')->insertGetId($data);

        return $result;
    }

    public function getUrl($image_id)
    {
        $url = Db::table('picture')->where('id',$image_id)->find();

        return $url['primary_file_uri'];
    }


}
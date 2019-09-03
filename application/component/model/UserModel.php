<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/13
 * Time: 10:12
 */

namespace app\component\model;

use think\Model;

class UserModel extends Model
{
    //开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'bigint';
    //定义自动写入时间戳字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $table = "user";
}
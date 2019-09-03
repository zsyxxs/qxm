<?php
namespace app\component\model;

use think\Model;

class NoticeModel extends Model
{
    //开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    //定义自动写入时间戳字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $table = "notice";
}
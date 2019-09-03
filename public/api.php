<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 应用入口文件 ]
// 绑定当前访问到index模块
define('BIND_MODULE','api');
define('ENABLE',1);
define('DISABLE',0);
define('LEVEL_ONE_MONEY',20000);//一级任务佣金（分）
define('LEVEL_TWO_MONEY',10000);//二级任务佣金（分）
define('LEVEL_BAD_MONEY',10000);//差评任务佣金（分）
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

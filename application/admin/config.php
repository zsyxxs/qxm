<?php

    return [
        // 默认模块名
        'default_module'         => 'admin',
        // 禁止访问模块
        'deny_module_list'       => ['common'],
        // 默认控制器名
        'default_controller'     => 'Managers',
        // 默认操作名
        'default_action'         => 'login',
        // 默认验证器
        'default_validate'       => '',
        // 默认的空控制器名
        'empty_controller'       => 'Error',
        // 操作方法后缀
        'action_suffix'          => '',
        // 自动搜索控制器
        'controller_auto_search' => false,
        // 视图输出字符串内容替换
        'view_replace_str'       => [
            'Ueditor' => '/public/ueditor',
            'LAYUI_CSS' => '/public/static/layui/css',
            'LAYUI_JS'  => '/public/static/layui',
            'ADMIN_CSS' => '/public/static/admin/css',
            'ADMIN_JS' => '/public/static/admin/js',
            'ADMIN_IMAGES' => '/public/static/admin/images',
            'ADMIN_LIB' => '/public/static/admin/lib',
        ],
    ];
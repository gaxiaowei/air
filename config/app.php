<?php
$config = [
    /**! 应用设置 !**/
    'app' => [
        'name' => 'web-server-user',   //应用名称
        'env' => 'develop',            //develop:开发环境 test:测试环境 beta:灰度环境 prod:线上环境 若是设为其他值为开发环境
    ],

    /**! debug设置 !**/
    'debug' => [
        'enable' => true,   //是否开启debug调试模式
        'handler' => null,  //自定义debug的处理类 但一定要继承Air\Kernel\Debug\Debug类
    ],

    /**! 日志设置 !**/
    'log' => [
        'type' => 'files',  //记录日志类型
        'level' => 'debug'
    ],

    /**! 缓存设置 !**/
    'cache' => [
        'drive' => 'apcu',      //驱动类型 apcu
        'prefix' => 'cache',    //前缀
    ]
];

return $config;
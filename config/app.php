<?php

$config = [
    'app' => [
        'debug' => true,
        'debug_handler' => null,       //自定义debug的处理类 但一定要继承Air\Kernel\Debug\Debug类
        'name' => 'web-server-user',   //应用名称
        'env' => 'develop',            //develop:开发环境 test:测试环境 beta:灰度环境 prod:线上环境 若是设为其他值为开发环境
    ]
];

return $config;
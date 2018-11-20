<?php

$config = [
    'log' => [
        'enable' => true,   //是否开启日志记录
        'type' => 'files',  //记录日志类型
        'level' => 'debug',
        'file' => __DIR__.'/../logs/air.log'
    ]
];

return $config;
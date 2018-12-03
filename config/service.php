<?php
$config = [
    'sw' => [
        'tcp' => [
            'enable' => false,
            'bind' => '0.0.0.0',
            'port' => 8000,
            'pack' => \Air\Pack\LenJsonPack::class
        ],

        'http' => [
            'enable' => true,
            'bind' => '0.0.0.0',
            'port' => 8001,
            'pack' => null
        ],

        /**! sw的属性设置 !**/
        'set' => [
            'reactor_num' => 1,
            'worker_num' => 1,
            'task_worker_num' => 0,
            'task_max_request' => 5000,
            'backlog' => 128,
            'dispatch_mode' => 2,
            'open_tcp_nodelay' => 1,
            'socket_buffer_size' => 4 * 1024 * 1024,
            'enable_reuse_port' => true,
            'max_connection' => 1024,
            'heartbeat_idle_time' => 120,
            'heartbeat_check_interval' => 60
        ]
    ],

    'servers' => [
        'user' => [
            '192.168.30.77:8000',
            '192.168.30.77:8000'
        ]
    ]
];

return $config;
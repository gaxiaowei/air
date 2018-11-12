<?php

$config = [
    'server' => [
        'set' => [
            'reactor_num' => 1,
            'worker_num' => 2,
            'task_worker_num' => 0,
            'task_max_request' => 5000,
            'backlog' => 128,
            'dispatch_mode' => 2,
            'open_tcp_nodelay' => 1,
            'socket_buffer_size' => 1024 * 1024 * 1024,
            'enable_reuse_port' => true,
            'max_connection' => 1024,
            'heartbeat_idle_time' => 120,
            'heartbeat_check_interval' => 60
        ]
    ]
];

return $config;
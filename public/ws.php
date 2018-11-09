<?php

use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new \Swoole\Server("0:0:0:0", 8777);

$server->set([
    'worker_num' => 3,
    'open_http_protocol' => 1
]);

$server->on('request', function (Request $request, Response $response) {
    $response->end('hello world');
});

$server->start();

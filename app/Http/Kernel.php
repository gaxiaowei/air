<?php
namespace App\Http;

use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Logic\Handler;
use App\Middleware\RouteConfigLoad;

class Kernel extends Handler
{
    protected $middleware = [
        RouteConfigLoad::class
    ];

    public function terminate(Request $request, Response $response)
    {
        parent::terminate($request, $response);
    }
}
<?php
namespace App\Http;

use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Logic\Handler;

class Kernel extends Handler
{
    public function terminate(Request $request, Response $response)
    {
        parent::terminate($request, $response);


    }
}
<?php
namespace App;

use Air\Kernel\Dispatcher\Dispatcher;
use Air\Kernel\Transfer\Request;
use Air\Kernel\Transfer\Response;

class Kernel extends Dispatcher
{
    public function terminate(Request $request, Response $response)
    {
        parent::terminate($request, $response);
    }
}
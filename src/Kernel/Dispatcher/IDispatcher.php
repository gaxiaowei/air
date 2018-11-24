<?php
namespace Air\Kernel\Dispatcher;

use Air\Kernel\Transfer\Request;
use Air\Kernel\Transfer\Response;

interface IDispatcher
{
    public function bootstrap();
    public function dispatch(Request $request) : Response;
    public function terminate(Request $request, Response $response);
}
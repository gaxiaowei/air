<?php
namespace Air\Kernel\Logic;

use Air\Air;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;

interface IHandler
{
    public function bootstrap();
    public function handle(Request $request) : Response;
    public function terminate(Request $request, Response $response);

    public static function getAir() : Air;
}
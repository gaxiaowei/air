<?php
namespace Air\Service\Server;

use Air\Kernel\InjectAir;
use App\Http\Kernel;

class Ng extends InjectAir implements IServer
{
    /**
     * @throws \Exception
     */
    public function run()
    {
        define('NG', true);

        $httpKernel = new Kernel(static::getAir(), static::getAir()->make('router'));

        $response = $httpKernel->handle(
            $request = \Air\Kernel\Logic\Handle\Request::createFromGlobals()
        );

        $response->send();

        $httpKernel->terminate($request, $response);
    }
}
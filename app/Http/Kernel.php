<?php
namespace App\Http;

use Air\Air;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Kernel
{
    private $air;
    private $router;

    public function __construct(Air $air)
    {
        $this->air = $air;

        $this->bootstrap();
    }

    /**
     * 请求-初始化
     */
    public function bootstrap()
    {
        $this->air->alias('request', Request::class);
        $this->air->alias('response', Response::class);

        $this->air->singleton(Request::class);
        $this->air->singleton(Response::class);
    }

    /**
     * 请求-执行
     * @param Request $request
     * @return mixed
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function handle(Request $request)
    {
        return $this->air->make('response');
    }

    /**
     * 请求-结束
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
//        $this->air->offsetUnset(Request::class);
//        $this->air->offsetUnset(Response::class);
    }

    /**
     * air-对象
     */
    public function getAir()
    {
        $this->air;
    }
}
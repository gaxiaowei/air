<?php
namespace Air\Kernel\Logic;

use Air\Air;
use Air\Kernel\InjectAir;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Routing\Router;

class Handler extends InjectAir implements IHandler
{
    /**
     * 路由
     * @var Router
     */
    protected $router;

    public function __construct(Air $air, Router $router)
    {
        parent::__construct($this->clone($air));

        $this->router = $router;
    }

    /**
     * 进入请求之前执行
     * @throws \Exception
     */
    public function bootstrap(){}

    /**
     * 请求执行
     * @param Request $request
     * @return Response|mixed
     * @throws \Exception
     */
    public function handle(Request $request) : Response
    {
        $this->getAir()->instance('request', $request);

        $this->bootstrap();

        try {
            $response = $this->routerDispatcher($request);
        } catch (\Exception $e) {

        } catch (\Error $e) {

        }

        return new Response();
    }

    /**
     * 请求结束
     * 做一些变量销毁、资源释放
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
    }

    /**
     * router 适配 执行控制器
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    private function routerDispatcher($request)
    {
        return $this->getAir()->make('router.dispatcher')->run($this->router, $request);
    }

    /**
     * 处理Sw协程共享变量问题 在Ng中不会
     * @param Air $obj
     * @return Air
     */
    private final function clone(Air $obj)
    {
        if (!defined('SW')) {
            return $obj;
        }

        $air = clone $obj;
        $air->registerBaseBinds();

        return $air;
    }
}
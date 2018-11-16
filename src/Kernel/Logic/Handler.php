<?php
namespace Air\Kernel\Logic;

use Air\Air;
use Air\Kernel\InjectAir;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Routing\Router;
use Air\Pipeline\Pipeline;

class Handler extends InjectAir implements IHandler
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * 全局中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 启动项
     * @var array
     */
    protected $bootstraps = [
        \App\Boot\RouteConfigLoad::class
    ];

    public function __construct(Air $air, Router $router)
    {
        parent::__construct($this->clone($air));

        $this->router = $router;
    }

    /**
     * 请求初始化
     * @throws \Exception
     */
    public function bootstrap()
    {
        /**! 顺序执行启动项 !**/
        foreach ($this->bootstraps as $boot) {
            $this->getAir()->make($boot)->bootstrap($this->getAir());
        }
    }

    /**
     * 请求执行
     * @param Request $request
     * @return Response|mixed
     * @throws \Exception
     */
    public function handle(Request $request) : Response
    {
        $this->bootstrap();
        $this->getAir()->instance('request', $request);

        try {
            $response = $this->sendRequestThroughRouter($request);
        } catch (\Exception $e) {

        } catch (\Throwable $e) {

        }

        return (new Response('Hello World'))->prepare($request);
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
     * 进入路由
     * @param $request
     * @return mixed
     */
    protected function sendRequestThroughRouter($request)
    {
        return (new Pipeline($this->getAir()))
            ->send($request)
            ->through($this->middleware ?? [])
            ->then($this->dispatchToRouter());
    }

    /**
     * 分发路由执行控制器
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            return $this->getAir()->make('router.dispatch')->run($this->router, $request);
        };
    }

    /**
     * 处理Sw协程共享变量问题 在Ng中不会
     * @param Air $obj
     * @return Air
     */
    private function clone(Air $obj)
    {
        if (!defined('SW')) {
            return $obj;
        }

        $air = clone $obj;
        $air->registerBaseBinds();

        return $air;
    }
}
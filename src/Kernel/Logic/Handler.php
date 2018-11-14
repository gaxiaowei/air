<?php
namespace Air\Kernel\Logic;

use Air\Kernel\Container\Container;
use Air\Kernel\InjectAir;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Routing\RouterDispatch;
use Air\Pipeline\Pipeline;

class Handler implements IHandler
{
    use InjectAir;

    /**
     * @var Container || null
     */
    protected $container = null;

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

    /**
     * 请求初始化
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException]
     */
    public function bootstrap()
    {
        /**! 顺序执行启动项 !**/
        foreach ($this->bootstraps as $boot) {
            static::getAir()->make($boot)->bootstrap(static::getAir());
        }
    }

    /**
     * 请求执行
     * @param Request $request
     * @return Response|mixed
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function handle(Request $request) : Response
    {
        $this->bootstrap();

        try {
            $response = $this->sendRequestThroughRouter($request);
        } catch (\Exception $e) {

        } catch (\Throwable $e) {

        }

        return static::getAir()->make('response', ['Hello World']);
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
            return $this->getAir()->make(RouterDispatch::class)->run($request);
        };
    }
}
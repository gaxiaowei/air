<?php
namespace Air\Kernel\Logic;

use Air\Air;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Routing\Router;
use Air\Kernel\Routing\RouterDispatch;
use Air\Pipeline\Pipeline;

class Handler implements IHandler
{
    /**
     * @var Air
     */
    protected $air;

    /**
     * 全局中间件
     * @var array
     */
    protected $middleware = [];

    public function __construct(Air $air)
    {
        $this->air = $air;

        /**! 注册别名 !**/
        $this->air->alias('request', Request::class);
        $this->air->alias('response', Response::class);
        $this->air->alias('router', Router::class);
    }

    /**
     * 请求初始化
     */
    public function bootstrap()
    {

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
            var_dump($e);
        } catch (\Throwable $e) {
            var_dump($e);
        }

        return $this->air->make('response', ['Hello World']);
    }

    /**
     * 请求结束
     * 做一些变量销毁工作、资源释放
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $this->air->offsetUnset(Request::class);
        $this->air->offsetUnset(Response::class);
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
            ->through($this->middleware)
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

    /**
     * 容器对象
     * @return Air
     */
    public function getAir() : Air
    {
        return $this->air;
    }
}
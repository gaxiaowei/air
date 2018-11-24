<?php
namespace Air\Kernel\Dispatcher;

use Air\Air;
use Air\Exception\FatalThrowableError;
use Air\Kernel\InjectAir;
use Air\Kernel\Routing\RouteDispatcher;
use Air\Kernel\Transfer\Request;
use Air\Kernel\Transfer\Response;
use Exception;
use Throwable;

class Dispatcher extends InjectAir implements IDispatcher
{
    /**
     * Handler constructor.
     * @param Air $air
     */
    final public function __construct(Air $air)
    {
        parent::__construct($air);
    }

    /**
     * 路由分发前执行
     */
    public function bootstrap(){}

    /**
     * 执行路由分发
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    final public function dispatch(Request $request) : Response
    {
        $this->getAir()->instance('request', $request);

        $this->bootstrap();

        try {
            $response = $this->routeDispatch($request);
        }  catch (Exception $e) {
            $this->reportException($e);

            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            $this->reportException($e = new FatalThrowableError($e));

            $response = $this->renderException($request, $e);
        }

        return $response;
    }

    /**
     * 请求结束
     * 做一些变量销毁、资源释放
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response){}

    /**
     * router 适配 执行控制器
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    private function routeDispatch($request)
    {
        return (new RouteDispatcher($this->getAir()))->run(
            $this->getAir()->get('router'),
            $request
        );
    }

    /**
     * 将异常导出到日志
     * @param Exception $e
     * @throws \Exception
     */
    final private function reportException(Exception $e)
    {
        $this->getAir()->get(\Air\Kernel\Debug\IDebug::class)->report($e);
    }

    /**
     * 将异常输出
     * @param $request
     * @param Exception $e
     * @return mixed
     * @throws \Exception
     */
    final private function renderException($request, Exception $e)
    {
        return $this->getAir()->get(\Air\Kernel\Debug\IDebug::class)->render($request, $e);
    }
}
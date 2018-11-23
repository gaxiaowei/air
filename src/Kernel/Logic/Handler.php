<?php
namespace Air\Kernel\Logic;

use Air\Air;
use Air\Exception\FatalThrowableError;
use Air\Kernel\InjectAir;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Air\Kernel\Routing\RouterDispatcher;
use Exception;
use Throwable;

class Handler extends InjectAir implements IHandler
{
    /**
     * Handler constructor.
     * @param Air $air
     */
    public function __construct(Air $air)
    {
        parent::__construct($this->clone($air));
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

        try {
            $response = $this->routerDispatcher($request);
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
    private function routerDispatcher($request)
    {
        return (new RouterDispatcher($this->getAir()))->run(
            $this->getAir()->get('router'),
            $request
        );
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

    /**
     * 将异常导出到日志
     * @param Exception $e
     * @throws \Exception
     */
    private function reportException(Exception $e)
    {
        $this->getAir()->get(\Air\Kernel\Debug\IHandler::class)->report($e);
    }

    /**
     * 将异常输出
     * @param $request
     * @param Exception $e
     * @return mixed
     * @throws \Exception
     */
    private function renderException($request, Exception $e)
    {
        return $this->getAir()->get(\Air\Kernel\Debug\IHandler::class)->render($request, $e);
    }
}
<?php
namespace Air\Kernel\Debug;

use Air\Kernel\InjectAir;
use Air\Kernel\Logic\Handle\JsonResponse;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use Exception;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

class DebugHandler extends InjectAir implements IHandler
{
    /**
     * @param Exception $e
     * @return mixed|void
     * @throws Exception
     */
    public function report(Exception $e)
    {
        try {
            $logger = $this->getAir()->make('log');
        } catch (Exception $ex) {
            throw $e; //throw the original exception
        }

        $logger->error(
            $e->getMessage(),
            ['exception' => $e]
        );
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return JsonResponse|Response|mixed
     * @throws Exception
     */
    public function render(Request $request, Exception $e)
    {
        $e = $this->prepareException($e);

        return $request->isXmlHttpRequest()
            ? $this->prepareJsonResponse($request, $e)
            : $this->prepareResponse($request, $e);
    }

    /**
     * @param Exception $e
     * @return Exception
     */
    protected function prepareException(Exception $e)
    {
        return $e;
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return Response
     * @throws \Exception
     */
    protected function prepareResponse(Request $request, Exception $e)
    {
        $content = '';
        if ($this->getAir()->get('config')->get('app.debug') && class_exists(Whoops::class)) {
            $content = $this->renderExceptionWithWhoops($e);
        }

        return Response::create($content, 200, $request->headers->all());
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return JsonResponse
     */
    protected function prepareJsonResponse(Request $request, Exception $e)
    {
        return new JsonResponse(
            $this->convertExceptionToArray($e),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @param Exception $e
     */
    protected function convertExceptionToArray(Exception $e)
    {
        return ;
    }

    /**
     * 渲染错误页面
     * @param Exception $e
     * @return mixed
     */
    protected function renderExceptionWithWhoops(Exception $e)
    {
        $whoops = new Whoops;
        $whoops->pushHandler($this->getWhoopsHandler());
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);

        return $whoops->handleException($e);
    }

    /**
     * @return PrettyPageHandler
     */
    protected function getWhoopsHandler()
    {
        $whoopsHandler = new PrettyPageHandler;
        $whoopsHandler->handleUnconditionally(true);

        return $whoopsHandler;
    }
}
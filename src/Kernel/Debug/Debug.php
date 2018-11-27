<?php
namespace Air\Kernel\Debug;

use Air\Kernel\InjectAir;
use Air\Kernel\Routing\Exception\RouteException;
use Air\Kernel\Transfer\JsonResponse;
use Air\Kernel\Transfer\RedirectResponse;
use Air\Kernel\Transfer\Request;
use Air\Kernel\Transfer\Response;
use Exception;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

class Debug extends InjectAir implements IDebug
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

        return $request->expectsJson()
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
     * 网页模式响应
     * @param Request $request
     * @param Exception $e
     * @return string
     * @throws \Exception
     */
    protected function prepareResponse(Request $request, Exception $e)
    {
        return $this->packResponse(
            $this->convertExceptionToResponse($e),
            $e
        );
    }

    /**
     * Json模式响应
     * @param Request $request
     * @param Exception $e
     * @return JsonResponse
     * @throws Exception
     */
    protected function prepareJsonResponse(Request $request, Exception $e)
    {
        return new JsonResponse(
            $this->convertExceptionToArray($e),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * 包装Response
     * @param  $response
     * @param Exception $e
     * @return Response|SymfonyRedirectResponse
     */
    protected function packResponse(SymfonyResponse $response, Exception $e)
    {
        if ($response instanceof SymfonyRedirectResponse) {
            $response = new RedirectResponse(
                $response->getTargetUrl(), $response->getStatusCode(), $response->headers->all()
            );
        } else {
            $response = new Response(
                $response->getContent(), $response->getStatusCode(), $response->headers->all()
            );
        }

        return $response->withException($e);
    }

    /**
     * 将异常封装成数组
     * @param Exception $e
     * @return array
     * @throws \Exception
     */
    protected function convertExceptionToArray(Exception $e)
    {
        return $this->getAir()->get('config')->get('app.debug') ? [
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ] : [
            'error' => $e->getMessage(),
        ];
    }

    /**
     * 将异常封装response对象响应
     * @param Exception $e
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    protected function convertExceptionToResponse(Exception $e)
    {
        $content = $this->renderExceptionWithSymfony($e, $this->getAir()->get('config')->get('app.debug'));

        $statusCode = 200;
        if ($e instanceof \ErrorException) {
            $statusCode = 500;
        } elseif ($e instanceof RouteException) {
            $statusCode = 404;
        }

        return SymfonyResponse::create(
            $content, $statusCode, []
        );
    }

    /**
     * 使用 symfony debug 调试
     * @param Exception $e
     * @param $debug
     * @return string
     */
    protected function renderExceptionWithSymfony(Exception $e, $debug)
    {
        return (new SymfonyExceptionHandler($debug))->getHtml(
            FlattenException::create($e)
        );
    }

    /**
     * 使用 Whoops debug 调试
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
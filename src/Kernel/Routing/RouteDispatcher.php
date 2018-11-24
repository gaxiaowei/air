<?php
namespace Air\Kernel\Routing;

use Air\Kernel\InjectAir;
use Air\Kernel\Transfer\JsonResponse;
use Air\Kernel\Transfer\Request;
use Air\Kernel\Transfer\Response;
use Air\Pipeline\Pipeline;
use ArrayObject;
use JsonSerializable;

class RouteDispatcher extends InjectAir
{
    /**
     * 匹配
     * @param Router $router
     * @param Request $request
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function run(Router $router, Request $request)
    {
        $route = $router->route($request->getRequestUri(), $request->getMethod());
        if (false === $route) {
            throw new Exception\RouteException("No matching route found [{$request->getRequestUri()}]");
        }

        return static::prepareResponse(
            $request, $this->runRoute($route, $request)
        );
    }

    /**
     * 执行
     * @param Route $route
     * @param Request $request
     * @return mixed
     */
    private function runRoute(Route $route, Request $request)
    {
        $this->getAir()->instance('route', $route);

        return (new Pipeline($this->getAir()))
            ->send($request)
            ->through($route->getMiddleware() ?? [])
            ->then(function ($request) {
                return static::prepareResponse(
                    $request, $this->runControllerDispatcher()
                );
            });
    }

    /**
     * @return ActionDispatcher
     * @throws \Exception
     */
    private function runControllerDispatcher()
    {
        return (new ActionDispatcher($this->getAir(), $this->getAir()->make('route')))->run();
    }

    /**
     * 准备好一个 response 对象返回
     * @param $request
     * @param $response
     * @return Response|JsonResponse
     */
    private static function prepareResponse($request, $response)
    {
        if (!$response instanceof Response &&
            ($response instanceof ArrayObject || $response instanceof JsonSerializable || is_array($response))
        ) {
            $response = new JsonResponse($response);
        } elseif (!$response instanceof Response) {
            $response = new Response($response);
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }

        return $response->prepare($request);
    }
}
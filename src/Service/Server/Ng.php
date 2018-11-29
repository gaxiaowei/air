<?php
namespace Air\Service\Server;

use Air\Kernel\InjectAir;
use Air\Kernel\Transfer\Request;
use Air\Tool\Arr;
use Air\Tool\Str;

class Ng extends InjectAir implements IServer
{
    /**
     * @throws \Exception
     */
    public function run()
    {
        $this->setRouterTree();

        $dispatcher = $this->getAir()->getDispatcher();

        $request = Request::createFromGlobals();
        $this->getAir()->instance('request', $request);

        $response = $dispatcher->dispatch($request);

        $response->send();

        $dispatcher->terminate($request, $response);
    }

    /**
     * 加载路由
     * @throws \Exception
     */
    private function setRouterTree()
    {
        $cache = $this->getAir()->make('cache');
        $router = $this->getAir()->make('router');

        $routesDirPath = $this->getAir()->getRoutesDirPath();
        $routesDirKey = crc32($routesDirPath);
        $routesLastModifyTime = filemtime($routesDirPath);

        if ($cache->get($routesDirKey) !== $routesLastModifyTime) {
            foreach (glob($routesDirPath.DIRECTORY_SEPARATOR.'*.php') as $file) {
                $router->group([], $file);
            }

            /**! 设置路由Tree到缓存中 !**/
            $cache->set(
                'router-tree',
                Arr::toJsonStr($router->getTree())
            );

            $cache->set($routesDirKey, $routesLastModifyTime);
        } else {
            $router->setTree(Str::jsonToArr($cache->get('router-tree')));
        }
    }
}
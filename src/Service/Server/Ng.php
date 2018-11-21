<?php
namespace Air\Service\Server;

use Air\Kernel\InjectAir;
use App\Http\Kernel;

class Ng extends InjectAir implements IServer
{
    /**
     * @throws \Exception
     */
    public function run()
    {
        define('NG', true);

        $this->setRouterTree();

        $httpKernel = new Kernel($this->getAir(), $this->getAir()->make('router'));

        $response = $httpKernel->handle(
            $request = \Air\Kernel\Logic\Handle\Request::createFromGlobals()
        );

        $response->send();

        $httpKernel->terminate($request, $response);
    }

    /**
     * 加载路由
     * @throws \Exception
     */
    private function setRouterTree()
    {
        $cache = $this->getAir()->make('cache');
        $routesDirKey = crc32($this->getAir()->getRoutesPath());
        $routesLastModifyTime = filemtime($this->getAir()->getRoutesPath());

        if ($cache->get($routesDirKey) !== $routesLastModifyTime) {
            $this->getAir()->make('router')->group([], function ($router) {
                /**@var $router \Air\Kernel\Routing\Router**/
                $router
                    ->namespace('App\Http')
                    ->prefix('api')
                    ->group([], $this->getAir()->getRoutesPath().'/api.php');

                $router
                    ->namespace('App\Http')
                    ->prefix('rpc')
                    ->group([], $this->getAir()->getRoutesPath().'/rpc.php');
            });

            /**! 设置路由tree到缓存中 !**/
            $cache->set(
                'router-tree',
                json_encode($this->getAir()->make('router')->getTree())
            );

            $cache->set($routesDirKey, $routesLastModifyTime);
        } else {
            $this->getAir()->make('router')->setTree(json_decode($cache->get('router-tree'), true));
        }
    }
}
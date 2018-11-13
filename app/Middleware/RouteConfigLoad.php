<?php
namespace App\Middleware;

use Air\Kernel\InjectAir;
use Closure;
use Air\Kernel\Logic\Handle\Request;

/**
 * 加载路由配置
 * Class RouteLoad
 * @package App\Middleware
 */
class RouteConfigLoad
{
    use InjectAir;

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->load();

        return $next($request);
    }

    private function load()
    {
        $router = static::getAir()->make('router');

//        require static::getAir()->getRoutesPath() . '/api.php';
//        require static::getAir()->getRoutesPath() . '/rpc.php';
    }
}
<?php
namespace App\Boot;

use Air\Air;

/**
 * Class RouteLoad
 * @package App\Middleware
 */
class RouteConfigLoad
{
    /**
     * @param Air $air
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function bootstrap(Air $air)
    {
        $air->make('router');
    }

    protected function getApiLastModifyTime(Air $air)
    {
        return filemtime($air->getRoutesPath() . '/api.php');
    }

    protected function getRpcLastModifyTime(Air $air)
    {
        return filemtime($air->getRoutesPath() . '/rpc.php');
    }
}
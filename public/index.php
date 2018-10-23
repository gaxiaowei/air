<?php
require __DIR__.'/../vendor/autoload.php';

$router = new \Air\Kernel\Routing\Router();
$router->match(['get', 'post'], '/index/{id}', 'Controller@index');
print_r($router->route('/index/123', 'put'));
exit;


/**! 全局对象 DI容器对象 所有的依赖通过它寻找 !**/
$di = \Air\Kernel\Container\Container::getInstance();
$di->singleton(\Air\Server\HttpServer::class);

try {
        /**! 加载swoole服务 !**/
        /**@var $httpServer \Air\Server\HttpServer**/
        $httpServer = $di->make(\Air\Server\HttpServer::class);
        $httpServer->start();
} catch (Exception $e) {
        var_dump($e);
}
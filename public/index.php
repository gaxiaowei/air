<?php
require __DIR__.'/../vendor/autoload.php';

$di = \Air\Kernel\Container\Container::getInstance();
$di->singleton(\Air\Server\HttpServer::class);

try {
        /**@var $httpServer \Air\Server\HttpServer**/
        $httpServer = $di->make(\Air\Server\HttpServer::class);
        $httpServer->start();
} catch (Exception $e) {
        var_dump($e);
}
<?php
/**@var $router \Air\Kernel\Routing\Router**/
use Air\Kernel\Routing\Router;

$router
->prefix('api')
->namespace('App\Http')
->group(['middleware' => []], function (Router $router) {
    $router->get('/index', 'Controller\Index@show');
});
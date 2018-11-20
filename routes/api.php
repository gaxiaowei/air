<?php
/**@var $router \Air\Kernel\Routing\Router**/
use Air\Kernel\Routing\Router;

$router->group(['middleware' => []], function (Router $router) {
    $router->get('/index', 'Controller\Index@show');
});

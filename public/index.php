<?php
require __DIR__.'/../vendor/autoload.php';

/**! 全局对象 DI容器对象 所有的依赖通过它寻找 !**/
$air = new \Air\Air(realpath(__DIR__.'/../'));
var_dump($air);
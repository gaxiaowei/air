<?php
require __DIR__.'/../vendor/autoload.php';

$model = new class extends \Air\Database\Model\Model
{
    protected $driver = 'mongo';
    protected $database = 'demo';
    protected $table = 'users';
};

$where[] = [

];

//$model::query()
//    ->where('a', '123123')
//    ->where('b', 'abc');

exit;
/**! 全局对象 DI容器对象 所有的依赖通过它寻找 !**/
$air = new \Air\Air(realpath(__DIR__.'/../'));
var_dump($air);
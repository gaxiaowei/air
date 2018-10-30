<?php
require __DIR__.'/../vendor/autoload.php';

$model = new class extends \Air\Database\Model\Model
{
    protected $driver = 'mongo';
    protected $database = 'demo';
    protected $table = 'users';
};

$model::query()->insert([
    'name' => 'john',
    'sex' => '男',
    'create_time' => new \MongoDB\BSON\UTCDateTime(time().'000')
]);

/**! 全局对象 DI容器对象 所有的依赖通过它寻找 !**/
$air = new \Air\Air(realpath(__DIR__.'/../'));
var_dump($air);
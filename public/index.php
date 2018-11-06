<?php
require __DIR__.'/../vendor/autoload.php';

$user = new class extends \Air\Database\Model\Model
{
    protected $driver = 'mongo';
    protected $database = 'demo';
    protected $table = 'users';
};

$t1 = microtime(true);
$insert = [];
for ($i = 0; $i < 1000; $i++) {
    $insert[] = [
        'name' => 'john'.$i
    ];
}

$user::query()->insert($insert);

$user::call()->execute();

$t2 = microtime(true);

//var_dump($result);
echo '耗时'.round($t2 - $t1, 3).'秒'.PHP_EOL;

exit;
/**! 全局对象 DI容器对象 所有的依赖通过它寻找 !**/
$air = new \Air\Air(realpath(__DIR__.'/../'));
var_dump($air);
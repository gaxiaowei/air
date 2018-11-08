<?php
define('FILE', __FILE__);

require __DIR__.'/../vendor/autoload.php';

$air = new Air\Air(realpath(__DIR__.'/../'));

try {
    $tcp = $air->make(Air\Server\Tcp::class);
    $tcp->run();
} catch (Exception $exception) {
    var_dump($exception);
}
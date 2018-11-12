<?php
require __DIR__.'/../vendor/autoload.php';

try {
    $air = new Air\Air(realpath(__DIR__.'/../'));
    $air->make(\Air\Service\Server\Protocol::class)->run();
} catch (Exception $exception) {
    var_dump($exception);
}
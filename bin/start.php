<?php
require __DIR__.'/../vendor/autoload.php';

try {
    $air = new Air\Air(realpath(__DIR__.'/../'));
    $air->make('protocol')->run();
} catch (Exception $exception) {
    var_dump($exception);
}
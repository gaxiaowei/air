<?php
require __DIR__.'/../vendor/autoload.php';

try {
    $air = new Air\Air(realpath(__DIR__.'/../'));

    /**@var $kernel \App\Http\Kernel**/
    $kernel = $air->make(\App\Http\Kernel::class);

    $response = $kernel->handle(
        $request = \Air\Kernel\Logic\Handle\Request::createFromGlobals()
    );

    $response->send();

    $kernel->terminate($request, $response);
} catch (Exception $exception) {
    var_dump($exception);
}
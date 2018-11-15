<?php
require __DIR__.'/../vendor/autoload.php';

$air = new Air\Air(realpath(__DIR__.'/../'));

/**! Sw模式运行 !**/
$air->singleton('sw', function (\Air\Air $air) {
    return $air->make(\Air\Service\Server\Sw::class);
});

/**! Ng模式运行 !**/
$air->singleton('ng', function (\Air\Air $air) {
    return $air->make(\Air\Service\Server\Ng::class);
});

/**! 路由 !**/
$air->singleton(\Air\Kernel\Routing\Router::class);

/**! 配置 !**/
$air->singleton('config', function(\Air\Air $air) {
    return $air->make(\Noodlehaus\Config::class, [$air->getConfigPath()]);
});

return $air;
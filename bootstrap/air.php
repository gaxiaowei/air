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

/**! 配置 !**/
$air->singleton('config', function(\Air\Air $air) {
    return $air->make(\Noodlehaus\Config::class, $air->getConfigPath());
});

/**! 日志 !**/
$air->singleton('log', function (\Air\Air $air) {
    return $air->make(\Air\Log\Logger::class);
});

/**! 缓存 !**/
$air->singleton('cache', function (\Air\Air $air) {
    return $air->make('cache.'.$air->make('config')->get('cache.drive'));
});

/**! 路由 !**/
$air->singleton(\Air\Kernel\Routing\Router::class);

return $air;
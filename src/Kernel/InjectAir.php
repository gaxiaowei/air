<?php
namespace Air\Kernel;

use Air\Air;

/**
 * 自动注入Air容器
 * Trait InjectAir
 * @package Air\Kernel
 */
abstract class InjectAir
{
    /**
     * 容器对象
     * @var Air
     */
    private static $air;

    /**
     * InjectAir constructor.
     * @param Air $air
     */
    public function __construct(Air $air)
    {
        if (!self::$air) {
            self::$air = $air;
        }
    }

    /**
     * 容器对象
     * @return mixed
     */
    final public static function getAir() : Air
    {
        return self::$air;
    }
}
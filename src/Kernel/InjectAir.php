<?php
namespace Air\Kernel;

use Air\Air;

/**
 * 自动注入Air容器
 * Trait InjectAir
 * @package Air\Kernel
 */
trait InjectAir
{
    /**
     * 容器对象
     * @var Air
     */
    protected static $air;

    /**
     * InjectAir constructor.
     * @param Air $air
     */
    public function __construct(Air $air)
    {
        if (!static::$air) {
            static::$air = $air;
        }
    }

    /**
     * 容器对象
     * @return mixed
     */
    public static function getAir() : Air
    {
        return static::$air;
    }
}
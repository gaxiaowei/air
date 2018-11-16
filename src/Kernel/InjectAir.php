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
    protected static $air;

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
    final public function getAir() : Air
    {
        return self::$air;
    }
}
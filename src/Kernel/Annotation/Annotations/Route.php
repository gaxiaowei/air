<?php
namespace Air\Kernel\Annotation\Annotations;

use Air\Kernel\Annotation\AnnotationMap;
use Air\Kernel\Annotation\Exception\AnnotationMapException;

class Route implements AnnotationMap
{
    private const NAME = 'Route';

    /**! 对应 Route->add() 的参数 !*/
    private $parameters = [
        'uri' => '',
        'method' => '',
        'handler' => '',
        'middleware' => null
    ];

    public function getAnnotationName()
    {
        return static::NAME;
    }

    public function get($name)
    {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        }

        throw new AnnotationMapException("The annotation parameter {$name} does not exist");
    }

    public function __call($name, $arguments)
    {
        return $this->get($name);
    }
}
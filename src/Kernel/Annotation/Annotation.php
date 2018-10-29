<?php
namespace Air\Kernel\Annotation;

class Annotation
{
    private $map = null;

    public function __construct(AnnotationMap $map)
    {
        $this->map = $map;
    }

    public function get($class)
    {

    }

    public function getClassAnnotation($name)
    {

    }

    public function getClassAnnotations() : array
    {

    }

    public function getMethodAnnotation($name)
    {

    }

    public function getMethodAnnotations() : array
    {

    }
}
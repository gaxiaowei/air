<?php
namespace Air\Kernel\Annotation;

interface AnnotationMap
{
    public function get($name);
    public function __call($name, $arguments);
    public function getAnnotationName();
}
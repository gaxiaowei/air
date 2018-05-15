<?php
namespace Air\Kernel\Container;

use Psr\Container\NotFoundExceptionInterface;

class BindingResolutionException extends \Exception implements NotFoundExceptionInterface
{

}
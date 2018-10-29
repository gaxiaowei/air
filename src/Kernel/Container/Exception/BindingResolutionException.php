<?php
namespace Air\Kernel\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

class BindingResolutionException extends \Exception implements NotFoundExceptionInterface
{

}
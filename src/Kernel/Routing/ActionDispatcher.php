<?php
namespace Air\Kernel\Routing;

use Air\Air;
use Air\Kernel\Transfer\Request;
use BadMethodCallException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

class ActionDispatcher
{
    /**
     * @var Air
     */
    private $air;

    /**
     * @var Route
     */
    private $route;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Object
     */
    private $controller;

    /**
     * ActionDispatcher constructor.
     * @param Air $air
     * @param Route $route
     * @param Request $request
     */
    public function __construct(Air $air, Route $route, Request $request)
    {
        $this->air = $air;
        $this->route = $route;
        $this->request = $request;
    }

    /**
     * 执行
     * @return mixed
     * @throws \Exception
     */
    public function run()
    {
        if ($this->isController()) {
            return $this->runController();
        }

        return $this->runCallable();
    }

    /**
     * 控制器执行
     * @return mixed
     * @throws \Exception
     */
    public function runController()
    {
        $this->controller = $this->controller ?: $this->air->make($this->getControllerClassName());

        return $this->dispatch(
            $this->getController(),
            $this->getControllerClassMethod()
        );
    }

    /**
     * 闭包执行
     * @return mixed
     * @throws \Exception
     */
    private function runCallable()
    {
        $callable = $this->route->getHandler();

        return $callable(...array_values($this->resolveMethodDependencies(
            $this->route->getMatches(), new ReflectionFunction($this->route->getHandler())
        )));
    }

    /**
     * @param $controller
     * @param $method
     * @return mixed
     * @throws \Exception
     */
    private function dispatch($controller, $method)
    {
        if (!method_exists($controller, $method)) {
            throw new BadMethodCallException("Method [{$method}] does not exist on [".get_class($controller).'].');
        }

        $parameters = $this->resolveMethodDependencies(
            $this->route->getMatches(),
            new ReflectionMethod($controller, $method)
        );

        return $controller->{$method}(...array_values($parameters));
    }

    /**
     * @param array $parameters
     * @param ReflectionFunctionAbstract $reflector
     * @return array
     * @throws \Exception
     */
    private function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        $instanceCount = 0;
        $values = array_values($parameters);

        foreach ($reflector->getParameters() as $key => $parameter) {
            $instance = $this->transformDependency($parameter, $parameters);

            if (!is_null($instance)) {
                $instanceCount++;

                $this->spliceIntoParameters($parameters, $key, $instance);
            } elseif (!isset($values[$key - $instanceCount]) && $parameter->isDefaultValueAvailable()) {
                $this->spliceIntoParameters($parameters, $key, $parameter->getDefaultValue());
            }
        }

        return $parameters;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param $parameters
     * @return mixed
     * @throws \Exception
     */
    private function transformDependency(ReflectionParameter $parameter, $parameters)
    {
        $class = $parameter->getClass();

        if ($class && !$this->alreadyInParameters($class->getName(), $parameters)) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            switch ($class->getName()) {
                case Request::class :
                    return $this->request;
                    break;
                case Route::class :
                    return $this->route;
                    break;
                default :
                    return $this->air->make($class->getName());
            }
        }

        return null;
    }

    /**
     * @param $class
     * @param array $parameters
     * @return bool
     */
    private function alreadyInParameters($class, array $parameters)
    {
        foreach ($parameters as $value) {
            if (call_user_func(function ($value) use ($class) {
                return $value instanceof $class;
            }, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $parameters
     * @param $offset
     * @param $value
     */
    private function spliceIntoParameters(array &$parameters, $offset, $value)
    {
        array_splice($parameters, $offset, 0, [$value]);
    }

    /**
     * 判断不是闭包
     * @return bool
     */
    private function isController()
    {
        return is_string($this->route->getHandler());
    }

    /**
     * @return Object
     */
    private function getController()
    {
        return $this->controller;
    }

    /**
     * @return mixed
     */
    private function getControllerClassName()
    {
        return $this->parseControllerCallback()[0];
    }

    /**
     * @return string
     */
    private function getControllerClassMethod()
    {
        return $this->parseControllerCallback()[1];
    }

    /**
     * @return array
     */
    private function parseControllerCallback()
    {
        $callback = $this->route->getHandler();

        return false !== strrpos($callback, '@') ? explode('@', $callback, 2) : [$callback, null];
    }
}

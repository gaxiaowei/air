<?php
namespace Air\Kernel\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements \ArrayAccess, ContainerInterface
{
        protected static $instance;

        private $instances = [];
        private $bindings = [];
        private $aliases = [];

        /**
         * build目标实例化的参数
         * @var array
         */
        private $buildArgs = [];

        /**
         * 这个实现步骤
         * 1 如果本实例不是共享实例要执行下面逻辑 否则直接返回
         *   1.1 如果已存在实例且给出的初始化参数为空 返回实例
         *   1.2 如果给出的参数非空对比上一次初始化参数如果相同 返回实例
         * @param $abstract
         * @param array $parameters
         * @return mixed
         */
        public function make($abstract, $parameters = [])
        {
                $abstract = $this->getAlias($abstract);

                if ($this->instances[$abstract] && count($parameters) === 0) {
                        return $this->instances[$abstract];
                }
        }

        /**
         * build一个对象 并返回
         * @param $concrete
         * @return mixed|object
         * @throws BindingResolutionException
         * @throws \ReflectionException
         */
        public function build($concrete)
        {
                if ($concrete instanceof Closure) {
                        return $concrete($this);
                }

                $reflector = new ReflectionClass($concrete);

                /** 检查类是否可实例化, 排除抽象类abstract和对象接口interface **/
                if (!$reflector->isInstantiable()) {
                        return $this->throwNotInstantiable($concrete);
                }

                /** 获取构造参数判断是否存在 **/
                $constructor = $reflector->getConstructor();
                if (is_null($constructor)) {
                        return new $concrete;
                }

                /** 取构造函数参数, 获取自动注入依赖项 **/
                $dependencies = $constructor->getParameters();
                $instances = $this->resolveDependencies($dependencies);

                /** 创建一个类的实例，给出的参数将传递到类的构造函数 **/
                $this->instances[$concrete] = $reflector->newInstanceArgs($instances);

                return $this->instances[$concrete];
        }

        /**
         * 指定别名
         * @param $alias
         * @param $abstract
         */
        public function alias($alias, $abstract)
        {
                $this->aliases[$alias] = $abstract;
        }

        /**
         * 有设置别名则返回 否则返回自己
         * @param $abstract
         * @return mixed
         */
        public function getAlias($abstract)
        {
              if (!isset($this->aliases[$abstract])) {
                      return $abstract;
              }

              return $this->getAlias($this->aliases[$abstract]);
        }

        protected function getClosure($abstract, $concrete)
        {
                return function ($container, $parameters = []) use ($abstract, $concrete) {
                        if ($abstract == $concrete) {
                                return $container->build($concrete);
                        }

                        return $container->make($concrete, $parameters);
                };
        }

        /**
         * @param array $dependencies
         * @return array
         * @throws BindingResolutionException
         * @throws \ReflectionException
         */
        protected function resolveDependencies(array $dependencies)
        {
                $results = [];

                foreach ($dependencies as $parameter) {
                        /** @var \ReflectionClass $dependency */
                        $dependency = $parameter->getClass();
                        if (is_null($dependency)) { //是变量, 有默认值则设置默认值
                                $results[] = $this->resolveNonClass($parameter);
                        } else { //是一个类递归解析
                                if (!$dependency->isInstantiable()) {
                                        throw new \InvalidArgumentException("Can't instantiate {$dependency->getName()}");
                                }

                                $results[] = $this->build($dependency->getName());
                        }
                }

                return $results;
        }

        /**
         * 当依赖找不到时抛出异常
         * @param $concrete
         * @throws BindingResolutionException
         */
        protected function throwNotInstantiable($concrete)
        {
                if (!empty($this->buildArgs)) {
                        $previous = implode(', ', $this->buildArgs);

                        $message = "Target [$concrete] is not instantiable while building [$previous].";
                } else {
                        $message = "Target [$concrete] is not instantiable.";
                }

                throw new BindingResolutionException($message);
        }

        /**
         * 解析参数中的默认参数
         * @param \ReflectionParameter $parameter
         * @return mixed
         */
        public function resolveNonClass(\ReflectionParameter $parameter)
        {
                if ($parameter->isDefaultValueAvailable()) {
                        return $parameter->getDefaultValue();
                }

                throw new \InvalidArgumentException($parameter->getName().' must be not null');
        }

        /**
         * 返回服务实例
         * @param string $id
         * @return mixed|object
         * @throws BindingResolutionException
         * @throws \ReflectionException
         */
        public function get($id)
        {
                if ($this->has($id)) {
                        return $this->instances[$id];
                }

                if (class_exists($id)) {
                        return $this->build($id);
                }

                throw new \InvalidArgumentException('class not found');
        }

        /**
         * 检查服务是否已注册
         * @param string $id
         * @return bool
         */
        public function has($id)
        {
                return isset($this->instances[$id]) ? true : false;
        }

        /**
         * 容器单例对象
         * @return static
         */
        public static function getInstance()
        {
                if (is_null(static::$instance)) {
                        static::$instance = new static;
                }

                return static::$instance;
        }

        public function offsetExists($offset)
        {

        }

        public function offsetGet($offset)
        {

        }

        public function offsetSet($offset, $value)
        {

        }

        public function offsetUnset($offset)
        {

        }
}
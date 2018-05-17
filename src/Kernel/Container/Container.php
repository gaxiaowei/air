<?php
namespace Air\Kernel\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

class Container implements \ArrayAccess, ContainerInterface
{
        /**
         * 容器对象
         * @var
         */
        protected static $instance;

        /**
         * 共享的服务实例
         * @var array
         */
        private $instances = [];

        /**
         * 服务的绑定
         * @var array
         */
        private $bindings = [];

        /**
         * 服务别名
         * @var array
         */
        private $aliases = [];

        /**
         * 实例化服务目标参数
         * @var array
         */
        private $bindingArgs = [];

        /**
         * 构建绑定实例依赖的参数
         * @var array
         */
        private $buildStackArgs = [];

        /**
         * Container constructor.
         */
        private function __construct()
        {
                /**! 将自己注册到容器中 !**/
                $this->instance(static::class, $this);

                /**! 设置别名访问服务 !**/
                $this->alias('di', static::class);
                $this->alias('container', static::class);
        }

        /**
         * 返回容器对象
         * @return static
         */
        public static function getInstance()
        {
                if (is_null(static::$instance)) {
                        static::$instance = new static;
                }

                return static::$instance;
        }

        /**
         * 向容器注册一个单利绑定
         * @param $abstract
         * @param null $concrete
         */
        public function singleton($abstract, $concrete = null)
        {
                $this->bind($abstract, $concrete, true);
        }

        /**
         * 向容器注册一个已经存在服务
         * @param $abstract
         * @param $instance
         * @return mixed
         */
        public function instance($abstract, $instance)
        {
                $this->removeAlias($abstract);

                $this->instances[$abstract] = $instance;

                return $instance;
        }

        /**
         * 向容器注册绑定
         * @param string  $abstract
         * @param \Closure|string|null  $concrete
         * @param bool $shared
         * @return $this
         */
        public function bind($abstract, $concrete = null, $shared = false)
        {
                /** 绑定之前先删除 **/
                $this->dropStaleInstance($abstract);

                if (is_null($concrete)) {
                        $concrete = $abstract;
                }

                if (!$concrete instanceof Closure) {
                        $concrete = $this->buildClosure($abstract, $concrete);
                }

                $this->bindings[$abstract] = compact('concrete', 'shared');

                return $this;
        }

        /**
         * 使用对象实例
         * @param $abstract
         * @param array $parameters
         * @return mixed
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        public function make($abstract, $parameters = [])
        {
                $abstract = $this->getAlias($abstract);

                if (isset($this->instances[$abstract])) {
                        return $this->instances[$abstract];
                }

                /** 保存参数 **/
                $this->bindingArgs[$abstract] = $parameters;

                $concrete = $this->getBuildClosure($abstract);
                if ($this->isBuildable($concrete, $abstract)) {
                        $object = $this->build($concrete);
                } else {
                        $object = $this->make($concrete);
                }

                /** 是共享服务设置到 instances 里 **/
                if ($this->isShared($abstract)) {
                        $this->instances[$abstract] = $object;
                }

                /** 删除保存参数 **/
                unset($this->bindingArgs[$abstract]);

                return $object;
        }

        /**
         * 创建一个新的服务并返回
         * @param $concrete
         * @return mixed
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        public function build($concrete)
        {
                if ($concrete instanceof Closure) {
                        return $concrete($this, $this->bindingArgs);
                }

                try {
                        $reflector = new ReflectionClass($concrete);

                        /** 检查类是否可实例化, 排除抽象类abstract和对象接口interface **/
                        if (!$reflector->isInstantiable()) {
                                return $this->throwNotInstantiable($concrete);
                        }

                        /** 当依赖没有找到 用户错误提示 **/
                        $this->buildStackArgs[] = $concrete;

                        /** 获取构造参数判断是否存在 **/
                        $constructor = $reflector->getConstructor();
                        if (is_null($constructor)) {
                                array_pop($this->buildStackArgs);

                                return new $concrete;
                        }

                        /** 取构造函数参数, 获取自动注入依赖项 **/
                        $dependencies = $constructor->getParameters();
                        $instances = $this->resolveDependencies($dependencies);

                        array_pop($this->buildStackArgs);

                        /** 创建一个类的实例，给出的参数将传递到类的构造函数 **/
                        return $reflector->newInstanceArgs($instances);
                } catch (\ReflectionException $e) {
                        throw new EntryNotFoundException("Target [{$concrete}] not found");
                }
        }

        /**
         * 设置服务别名
         * @param $alias
         * @param $abstract
         */
        public function alias($alias, $abstract)
        {
                $this->aliases[$alias] = $abstract;
        }

        /**
         * 服务是否为共享
         * @param $abstract
         * @return bool
         */
        public function isShared($abstract)
        {
                return isset($this->instances[$abstract]) ||
                        (isset($this->bindings[$abstract]['shared']) &&
                                $this->bindings[$abstract]['shared'] === true);
        }

        /**
         * 服务别名是否存在
         * @param $name
         * @return bool
         */
        public function isAlias($name)
        {
                return isset($this->aliases[$name]);
        }

        /**
         * 返回服务的别名
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

        /**
         * 解决依赖
         * @param array $dependencies
         * @return array
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        protected function resolveDependencies(array $dependencies)
        {
                $results = [];

                foreach ($dependencies as $dependency) {
                        $results[] = is_null($dependency->getClass())
                                ? $this->resolvePrimitive($dependency)
                                : $this->resolveClass($dependency);
                }

                return $results;
        }

        /**
         * 解析类
         * @param ReflectionParameter $parameter
         * @return mixed
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        protected function resolveClass(ReflectionParameter $parameter)
        {
                try {
                        return $this->make($parameter->getClass()->getName());
                } catch (BindingResolutionException $e) {
                        if ($parameter->isOptional()) {
                                return $parameter->getDefaultValue();
                        }

                        throw $e;
                }
        }

        /**
         * 解析参数
         * @param ReflectionParameter $parameter
         * @return mixed
         * @throws BindingResolutionException
         */
        public function resolvePrimitive(ReflectionParameter $parameter)
        {
                if ($parameter->isDefaultValueAvailable()) {
                        return $parameter->getDefaultValue();
                }

                $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

                throw new BindingResolutionException($message);
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
         * 获取已注册的闭包
         * @param $abstract
         * @return mixed
         */
        protected function getBuildClosure($abstract)
        {
                if (isset($this->bindings[$abstract])) {
                        return $this->bindings[$abstract]['concrete'];
                }

                return $abstract;
        }

        /**
         * 构建一个闭包并返回
         * @param $abstract
         * @param $concrete
         * @return Closure
         */
        protected function buildClosure($abstract, $concrete)
        {
                return function (Container $container, $parameters = []) use ($abstract, $concrete) {
                        if ($abstract == $concrete) {
                                return $container->build($concrete);
                        }

                        return $container->make($concrete, $parameters);
                };
        }

        /**
         * 删除服务别名
         * @param $delAbstract
         */
        protected function removeAlias($delAbstract)
        {
                foreach ($this->aliases as $alias => $abstract) {
                        if ($abstract == $delAbstract) {
                                unset($this->aliases[$alias]);
                        }
                }
        }

        /**
         * 删除已经存在的实例和别名
         * @param $abstract
         */
        protected function dropStaleInstance($abstract)
        {
                unset($this->instances[$abstract], $this->aliases[$abstract]);
        }

        /**
         * 是否可构建
         * @param $concrete
         * @param $abstract
         * @return bool
         */
        protected function isBuildable($concrete, $abstract)
        {
                return $concrete === $abstract || $concrete instanceof Closure;
        }

        /**
         * 返回服务实例
         * @param string $id
         * @return mixed
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        public function get($id)
        {
                if ($this->has($id)) {
                        return $this->make($id);
                }

                throw new EntryNotFoundException("Target [{$id}] not found");
        }

        /**
         * 检查服务是否已注册
         * @param string $id
         * @return bool
         */
        public function has($id)
        {
                return isset($this->bindings[$id]) || isset($this->instances[$id]) || $this->isAlias($id);
        }

        /**
         * @param mixed $key
         * @return bool
         */
        public function offsetExists($key)
        {
                return $this->has($key);
        }

        /**
         * @param mixed $key
         * @return mixed
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        public function offsetGet($key)
        {
                return $this->make($key);
        }

        /**
         * @param mixed $key
         * @param mixed $value
         */
        public function offsetSet($key, $value)
        {
                $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
                        return $value;
                });
        }

        /**
         * @param mixed $key
         */
        public function offsetUnset($key)
        {
                $key = $this->getAlias($key);

                unset($this->bindings[$key], $this->instances[$key]);
        }

        /**
         * @param $key
         * @return mixed
         * @throws BindingResolutionException
         * @throws EntryNotFoundException
         */
        public function __get($key)
        {
                return $this->get($key);
        }

        /**
         * @param $key
         * @param $value
         */
        public function __set($key, $value)
        {
                $this->offsetSet($key, $value);
        }
}
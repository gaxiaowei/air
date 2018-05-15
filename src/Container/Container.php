<?php
namespace Air\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements \ArrayAccess, ContainerInterface
{
        protected static $instance;

        private $instances  = [];
        private $closures   = [];
        private $aliases    = [];

        public function bind($abstract, $concrete = null, $shared = false)
        {
                if (is_null($concrete)) {
                        $concrete = $abstract;
                }

                if (! $concrete instanceof Closure) {
                        $concrete = $this->getClosure($abstract, $concrete);
                }

                $this->closures[$abstract] = compact('concrete', 'shared');
        }

        /**
         * 创建一个对象
         * @param $concrete
         * @return mixed|object
         * @throws \ReflectionException | \InvalidArgumentException
         */
        public function build($concrete)
        {
                if (isset($this->instances[$concrete])) {
                        return $this->instances[$concrete];
                }

                $reflector = new ReflectionClass($concrete);

                /** 检查类是否可实例化, 排除抽象类abstract和对象接口interface **/
                if (!$reflector->isInstantiable()) {
                        throw new \InvalidArgumentException("Can't instantiate {$concrete}");
                }

                /** 获取构造参数判断是否存在 **/
                $constructor = $reflector->getConstructor();
                if (is_null($constructor)) {
                        return new $concrete;
                }

                /** 取构造函数参数, 获取依赖 **/
                $dependencies = $constructor->getParameters();
                $dependencies = $this->resolveDependencies($dependencies);

                /** 创建一个类的实例，给出的参数将传递到类的构造函数 **/
                $instances =  $reflector->newInstanceArgs($dependencies);
                $this->instances[$concrete] = $instances;

                return $instances;
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
         * 解析参数获取依赖项
         * @param array $dependencies
         * @return array
         * @throws \ReflectionException | \InvalidArgumentException
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

        public function get($id)
        {

        }

        public function has($id)
        {

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
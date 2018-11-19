<?php
namespace Air\Kernel\Routing;

use Air\Kernel\Routing\Exception\RouteException;
use InvalidArgumentException;

/**
 * Class Router
 * @package Air\Kernel\Routing
 *
 * @method get(string $uri, \Closure|string $handle)
 * @method post(string $uri, \Closure|string $handle)
 * @method put(string $uri, \Closure|string $handle)
 * @method patch(string $uri, \Closure|string $handle)
 * @method delete(string $uri, \Closure|string $handle)
 * @method head(string $uri, \Closure|string $handle)
 * @method options(string $uri, \Closure|string $handle)
 * @method any(string $uri, \Closure|string $handle)
 * @method \Air\Kernel\Routing\Router domain(string $domain)
 * @method \Air\Kernel\Routing\Router prefix(string $prefix)
 * @method \Air\Kernel\Routing\Router middleware(array|string $middleware)
 * @method \Air\Kernel\Routing\Router namespace(string $namespace)
 */
class Router
{
    private $allowed_method = ['get', 'head', 'post', 'put', 'patch', 'delete', 'options', 'any'];
    private $allowed_attribute = ['domain', 'prefix', 'middleware', 'namespace'];

    private $method_all = 'ANY';
    private $handler = '#handler';
    private $separator = '/';
    private $parameter = '*';

    private $tree = [];
    private $attributes = [];
    private $groupStack = [];

    /**
     * @param array $attributes
     * @param $routes
     */
    public function group(array $attributes, $routes)
    {
        $this->updateGroupStack($attributes);

        $this->loadRoutes($routes);

        array_pop($this->groupStack);
    }

    /**
     * @param $method
     * @param $uri
     * @param $handle
     * @return Router
     */
    public function match($method, $uri, $handle)
    {
        return $this->register(...func_get_args());
    }

    /**
     * @param $method
     * @param $args
     * @return Router
     * @throws RouteException
     */
    public function __call($method, $args)
    {
        if (in_array($method, $this->allowed_method)) {
            return $this->register($method, ...$args);
        }

        if (in_array($method, $this->allowed_attribute)) {
            if ($method === 'middleware') {
                return $this->attribute($method, is_array($args[0]) ? $args[0] : $args);
            }

            return $this->attribute($method, $args[0]);
        }

        throw new RouteException('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
    }

    /**
     * 搜索tree里面存在给定的路由
     * @param $path
     * @param $method
     * @return Route|bool|mixed
     */
    public function route($path, $method)
    {
        return $this->searchTree($this->split($path), $method);
    }

    /**
     * 分割
     * @param string $string
     * @return array|string
     */
    private function split(string $string)
    {
        if (!$string) {
            return $string;
        }

        return explode(
            $this->separator,
            trim($string, $this->separator)
        );
    }

    /**
     * 设置给定的属性值
     * @param $key
     * @param $value
     * @return $this
     */
    private function attribute($key, $value)
    {
        if (!in_array($key,$this->allowed_attribute)) {
            throw new InvalidArgumentException("Attribute [{$key}] does not exist.");
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * 统一注册路由
     * @param $method
     * @param $uri
     * @param $handle
     * @return $this
     */
    private function register($method, $uri, $handle)
    {
        $action = $this->compileAction($handle);
        if ($action['prefix']) { /**! 拼接完整的URI !**/
            $uri = rtrim($action['prefix'], '\\').'\\'.ltrim($uri, '\\');
        }

        $this->insertTree($this->split($uri), $uri, (array)$method, $action);

        return $this;
    }

    /**
     * 拼装 action
     * @param $handle
     * @return array
     */
    private function compileAction($handle)
    {
        $action = $this->attributes;
        if ($this->hasGroupStack()) {
            $action = $this->mergeAttributesWithLastGroup($action);
        }

        if (is_string($handle) || $handle instanceof \Closure) {
            $action['handler'] = $handle;
        }

        /**! 没有设置的属性设定默认值 !**/
        foreach ($this->allowed_attribute as $attr) {
            if (!array_key_exists($attr, $action)) {
                $action[$attr] = '';
            }
        }

        /**! 拼接完整的Controller命名空间路径 !**/
        if (isset($action['namespace']) && !is_callable($action['handler'])) {
            $action['handler'] = rtrim($action['namespace'], '\\').'\\'.ltrim($action['handler'], '\\');
        }

        return $action;
    }

    /**
     * 插入节点树
     * @param $tokens
     * @param $uri
     * @param $methods
     * @param mixed ...$action
     */
    private function insertTree($tokens, $uri, $methods, $action)
    {
        $matches = [];

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $tree = &$this->tree[$method] ?? [];

            foreach ($tokens as $token) {
                if (strpos($token, '{') !== false) {
                    $matches[substr($token, 1, -1)] = null;

                    $token = $this->parameter;
                }

                if (!isset($tree[$token])) {
                    $tree[$token] = [];
                }

                $tree = &$tree[$token];
            }

            $tree[$this->handler] = [
                'uri' => $uri,
                'method' => $method,
                'action' => $action,
                'matches' => $matches
            ];
        }
    }

    /**
     * 查找
     * @param $tokens
     * @param $method
     * @return Route|bool|mixed
     */
    private function searchTree($tokens, $method)
    {
        $nodes = $this->tree[strtoupper($method)] ?? $this->tree[$this->method_all] ?? false;

        if (false !== $nodes) {
            foreach ($tokens as $token) {
                if (isset($nodes[$token])) {
                    $nodes = $nodes[$token];
                } else if (isset($nodes[$this->parameter])) {
                    $nodes = $nodes[$this->parameter];
                    $matches[] = $token;
                } else {
                    return false;
                }
            }

            if (isset($nodes[$this->handler])) {
                foreach ($nodes[$this->handler]['matches'] as $key => $val) {
                    $nodes[$this->handler]['matches'][$key] = array_shift($matches);
                }

                return new Route($nodes[$this->handler]);
            }
        }

        return $nodes;
    }

    /**
     * @return bool
     */
    private function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    /**
     * @param $new
     * @return array
     */
    private function mergeAttributesWithLastGroup($new)
    {
        return RouteGroup::merge($new, end($this->groupStack));
    }

    /**
     * 递归调用属性合并
     * @param array $attributes
     */
    private function updateGroupStack(array $attributes)
    {
        if (!empty($this->groupStack)) {
            $attributes = RouteGroup::merge($attributes, end($this->groupStack));
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * 加载路由配置
     * @param $routes
     */
    private function loadRoutes($routes)
    {
        if ($routes instanceof \Closure) {
            $routes($this);
        } else {
            $router = $this;

            include $routes;
        }
    }
}
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
    const ALLOWED_METHOD = ['get', 'head', 'post', 'put', 'patch', 'delete', 'options', 'any'];
    const ALLOWED_ATTRIBUTE = ['domain', 'prefix', 'middleware', 'namespace'];

    const METHOD_ALL = 'ANY';
    const HANDLER = '#handler';
    const SEPARATOR = '/';
    const PARAMETER = '*';

    private $tree = [];
    private $attributes = [];
    private $groupStack = [];

    /**
     * 设置分组路由
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
        if (in_array($method, static::ALLOWED_METHOD)) {
            return $this->register($method, ...$args);
        }

        if (in_array($method, static::ALLOWED_ATTRIBUTE)) {
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
     * 获取路由节点数组
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * 设置路由节点数组
     * @param array $tree
     */
    public function setTree($tree = [])
    {
        $this->tree = $tree;
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
            static::SEPARATOR,
            trim($string, static::SEPARATOR)
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
        if (!in_array($key, static::ALLOWED_ATTRIBUTE)) {
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
        $this->insertTree($uri, (array)$method, $this->compileAction($handle));

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
        foreach (static::ALLOWED_ATTRIBUTE as $attr) {
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
     * @param $uri
     * @param $methods
     * @param mixed ...$action
     */
    private function insertTree($uri, $methods, $action)
    {
        /**! 拼接完整的URI !**/
        if (!empty($action['prefix'])) {
            $uri = trim($action['prefix'], '/').'/'.trim($uri, '/');
        }

        $tokens = $this->split($uri);
        $matches = [];

        foreach ($methods as $method) {
            $method = strtoupper($method);
            $tree = &$this->tree[$method] ?? [];

            foreach ($tokens as $token) {
                if (strpos($token, '{') !== false) {
                    $matches[substr($token, 1, -1)] = null;

                    $token = static::PARAMETER;
                }

                if (!isset($tree[$token])) {
                    $tree[$token] = [];
                }

                $tree = &$tree[$token];
            }

            $tree[static::HANDLER] = [
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
        $nodes = $this->tree[strtoupper($method)] ?? $this->tree[static::METHOD_ALL] ?? false;

        if (false !== $nodes) {
            foreach ($tokens as $token) {
                if (isset($nodes[$token])) {
                    $nodes = $nodes[$token];
                } else if (isset($nodes[static::PARAMETER])) {
                    $nodes = $nodes[static::PARAMETER];
                    $matches[] = $token;
                } else {
                    return false;
                }
            }

            if (isset($nodes[static::HANDLER])) {
                foreach ($nodes[static::HANDLER]['matches'] as $key => $val) {
                    $nodes[static::HANDLER]['matches'][$key] = array_shift($matches);
                }

                return new Route($nodes[static::HANDLER]);
            }
        }

        return $nodes;
    }

    /**
     * group调用是否有参数
     * @return bool
     */
    private function hasGroupStack()
    {
        return !empty($this->groupStack);
    }

    /**
     * 将group的属性合并
     * @param $new
     * @return array
     */
    private function mergeAttributesWithLastGroup($new)
    {
        return $this->mergeGroup($new, end($this->groupStack));
    }

    /**
     * 递归调用属性合并
     * @param array $attributes
     */
    private function updateGroupStack(array $attributes)
    {
        if (!empty($this->groupStack)) {
            $attributes = $this->mergeGroup($attributes, end($this->groupStack));
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

    /**
     * 属性合并
     * @param $new
     * @param $old
     * @return array
     */
    private function mergeGroup($new, $old)
    {
        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        $new = [
            'namespace' => $this->mergeGroupNamespace($new, $old),
            'prefix' => $this->mergeGroupPrefix($new, $old)
        ];

        return array_merge_recursive(
            ['middleware' => (array)($old['middleware'] ?? [])],
            $new
        );
    }

    /**
     * @param $new
     * @param $old
     * @return null|string
     */
    private function mergeGroupNamespace($new, $old)
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace'])
                ? trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\')
                : trim($new['namespace'], '\\');
        }

        return $old['namespace'] ?? null;
    }

    /**
     * @param $new
     * @param $old
     * @return null|string]
     */
    private function mergeGroupPrefix($new, $old)
    {
        $old = $old['prefix'] ?? null;

        return isset($new['prefix']) ? trim($old, '/').'/'.trim($new['prefix'], '/') : $old;
    }
}
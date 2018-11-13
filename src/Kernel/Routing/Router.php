<?php
namespace Air\Kernel\Routing;

/**
 * Class Router
 * @package Air\Kernel\Routing
 *
 * @method get($path, $callback, $middleware = [])
 * @method post($path, $callback, $middleware = [])
 * @method put($path, $callback, $middleware = [])
 * @method patch($path, $callback, $middleware = [])
 * @method delete($path, $callback, $middleware = [])
 * @method head($path, $callback, $middleware = [])
 * @method options($path, $callback, $middleware = [])
 * @method any($path, $callback, $middleware = [])
 * @method match($method, $path, $callback, $middleware = [])
 */
class Router
{
    const HTTP_METHOD = ['get', 'head', 'post', 'put', 'patch', 'delete', 'options', 'any', 'match'];
    const METHOD_ALL = 'ANY';
    const HANDLER = '#handler';
    const SEPARATOR = '/';
    const PARAMETER = '*';

    private $tree = [];

    private $groupStack = [];

    public function add($method, $path, $callback, $middleware = [])
    {
        $groupStack = [];
        foreach ($this->groupStack as $attribute) {
            if (array_key_exists('middleware', $attribute)) {
                $groupStack = array_merge($groupStack, (array)$attribute['middleware']);
            }
        }

        $middleware = array_merge($groupStack, (array)$middleware);
        $middleware = array_unique($middleware);

        $this->insertTree($this->split($path), $callback, (array)$middleware, (array)$method, $path);

        return $this;
    }

    public function route($path, $method)
    {
        return $this->searchTree($this->split($path), $method);
    }

    public function group(array $attributes, \Closure $callback)
    {
        $this->groupStack[] = $attributes;

        $callback($this);

        array_pop($this->groupStack);
    }

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

    private function insertTree($tokens, $callback, $middleware, $methods, $uri)
    {
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
                'handler' => $callback,
                'matches' => $matches,
                'middleware' => $middleware
            ];
        }
    }

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

                return $nodes[static::HANDLER];
            }
        }

        return $nodes;
    }

    public function __call($name, $args)
    {
        if (in_array($name, static::HTTP_METHOD)) {
            if ($name === 'match') {
                $args[0] = (array)$args[0];
                if (in_array('any', $args[0])) {
                    $name = 'any';
                    unset($args[0]);
                }
            }

            if ($name !== 'match') {
                array_unshift($args, strtoupper($name));
            }

            return $this->add(...$args);
        }

        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}
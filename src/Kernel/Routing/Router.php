<?php
namespace Air\Kernel\Routing;

use Psr\Container\ContainerInterface;

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
 */
class Router
{
    const METHOD = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    const AT = '@';
    const HANDLER = '#';
    const SEPARATOR = '/';
    const PARAMETER = ':';

    /**
     * Ioc容器
     * @var ContainerInterface
     */
    private $container;

    private $treeStructure = [];

    private $groupStack = [];

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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

        $this->addTree($this->treeStructure, $this->split($path), $callback, (array)$middleware, (array)$method, $path);

        return $this;
    }

    public function match($path)
    {
        return $this->resolve($this->treeStructure, $this->split($path));
    }

    public function group(array $attributes, \Closure $callback)
    {
        $this->groupStack[] = $attributes;

        $callback($this);

        array_pop($this->groupStack);
    }

    public function middleware($middleware, \Closure $callback)
    {
        $this->group((array)$middleware, $callback);
    }

    private function split(string $string)
    {
        if (!$string) {
            return $string;
        }

        return explode(static::SEPARATOR, trim($string, static::SEPARATOR));
    }

    private function addTree(&$node, $tokens, $callback, $middleware, $method, $uri)
    {
        if (!array_key_exists(static::PARAMETER, $node)) {
            $node[static::PARAMETER] = [];
        }

        $token = array_shift($tokens);
        if (strncmp(static::PARAMETER, $token, 1) === 0) {
            $node = &$node[static::PARAMETER];
            $token = substr($token, 1);
        }

        if ($token === null) {
            $node[self::HANDLER] = [
                'callback' => $callback,
                'middleware' => $middleware,
                'method' => $method,
                'uri' => $uri
            ];

            return;
        }

        if (!array_key_exists($token, $node)) {
            $node[$token] = [];
        }

        $this->addTree($node[$token], $tokens, $callback, $middleware, $method, $uri);
    }

    private function resolve($node, $tokens, $params = [])
    {
        $token = array_shift($tokens);
        if ($token === null && array_key_exists(static::HANDLER, $node)) {
            return array_merge($node[static::HANDLER], ['matches' => $params]);
        }

        if (array_key_exists($token, $node)) {
            return $this->resolve($node[$token], $tokens, $params);
        }

        foreach ($node[static::PARAMETER] as $childToken => $childNode) {
            if ($token === null && array_key_exists(static::HANDLER, $childNode)) {
                return array_merge($childNode[static::HANDLER], ['matches' => $params]);
            }

            $handler = $this->resolve($childNode, $tokens, array_merge($params, [$childToken => $token]));
            if ($handler !== false) {
                return $handler;
            }
        }

        return false;
    }

    public function __call($name, $args)
    {
        $name = strtoupper($name);
        if (in_array($name, static::METHOD)) {
            array_unshift($args, $name);

            return call_user_func_array([$this, 'add'], $args);
        }

        throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }
}
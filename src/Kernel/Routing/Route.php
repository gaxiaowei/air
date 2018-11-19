<?php
namespace Air\Kernel\Routing;

class Route
{
    private $params = [];

    public function __construct(array $route = [])
    {
        $this->params = $route;
    }

    public function getUri()
    {
        return $this->params['uri'];
    }

    public function getMethod()
    {
        return $this->params['method'];
    }

    public function getMatches()
    {
        return $this->params['matches'];
    }

    public function getAction($key = null)
    {
        return is_null($key) ? $this->params['action'] : $this->params['action'][$key];
    }

    public function getHandler()
    {
        return $this->getAction('handler');
    }

    public function getNamespace()
    {
        return $this->getAction('namespace');
    }

    public function getPrefix()
    {
        return $this->getAction('prefix');
    }

    public function getMiddleware()
    {
        return $this->getAction('middleware') == '' ? [] : $this->getAction('middleware');
    }

    public function getDomain()
    {
        return $this->getAction('domain');
    }
}
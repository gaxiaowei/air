<?php
namespace Air;

use Air\Kernel\Container\Container;

class Air extends Container
{
    /**
     * Air 版本
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Air 安装路径
     * @var string
     */
    private $root;

    /**
     * Air constructor.
     * @param string $path
     */
    public function __construct($path = '')
    {
        $this->setPath($path);

        $this->registerBaseBinds();

        $this->registerCoreAliases();
    }

    /**
     * 返回 Air 框架版本
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->root = $path;
    }

    public function getRootPath()
    {
        return $this->root.DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->getRootPath().'config';
    }

    /**
     * @return string
     */
    public function getAppPath()
    {
        return $this->getRootPath().'app';
    }

    /**
     * @return string
     */
    public function getRoutesPath()
    {
        return $this->getRootPath().'routes';
    }

    /**
     * @return string
     */
    public function getLogsPath()
    {
        return $this->getRootPath().DIRECTORY_SEPARATOR.'/logs';
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLogsFilePath()
    {
        return $this->getLogsPath().DIRECTORY_SEPARATOR.$this->get('config')->get('app.env').'.log';
    }

    /**
     * 注册核心的服务别名
     */
    public function registerCoreAliases()
    {
        foreach ([
             'app' => \Air\Kernel\Container\Container::class,
             'request' => \Air\Kernel\Logic\Handle\Request::class,
             'response' => \Air\Kernel\Logic\Handle\Response::class,
             'response.json' => \Air\Kernel\Logic\Handle\JsonResponse::class,
             'route' => \Air\Kernel\Routing\Route::class,
             'router' => \Air\Kernel\Routing\Router::class,
             'router.dispatcher' => \Air\Kernel\Routing\RouterDispatcher::class,
             'pipeline' => \Air\Pipeline\Pipeline::class,
             'logger' => \Air\Log\Logger::class,
             'cache.apcu' => \Air\Cache\Apcu::class,
             'cache.file' => \Air\Cache\Apcu::class,
             'cache.redis' => \Air\Cache\Apcu::class,
        ] as $key => $alias) {
            $this->alias($key, $alias);
        }
    }

    /**
     * 注册基础服务绑定
     */
    public function registerBaseBinds()
    {
        static::setInstance($this);

        $this->instance(Container::class, $this);
        $this->instance(static::class, $this);
    }
}
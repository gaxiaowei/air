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

    /**
     * @return string
     */
    public function getConfigPath()
    {
        return $this->root.DIRECTORY_SEPARATOR.'config';
    }

    /**
     * @return string
     */
    public function getAppPath()
    {
        return $this->root.DIRECTORY_SEPARATOR.'/app';
    }

    /**
     * @return string
     */
    public function getRoutesPath()
    {
        return $this->root.DIRECTORY_SEPARATOR.'/routes';
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
             'router' => \Air\Kernel\Routing\Router::class,
             'router.dispatch' => \Air\Kernel\Routing\RouterDispatch::class,
             'protocol' => \Air\Service\Server\Sw::class,
             'pipeline' => \Air\Pipeline\Pipeline::class
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
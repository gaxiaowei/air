<?php
namespace Air;

use Air\Exception\FatalThrowableError;
use Air\Kernel\Container\Container;
use Air\Kernel\Debug\Debug;
use Air\Kernel\Debug\IDebug;
use Air\Kernel\Dispatcher\Dispatcher;
use Air\Kernel\Routing\Router;
use Air\Log\Logger;
use Air\Service\Server\IServer;
use Air\Service\Server\Ng;
use Air\Service\Server\Sw;
use ErrorException;
use Exception;

final class Air extends Container
{
    /**
     * Air 版本
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * 项目路径
     * @var string
     */
    private $root;

    /**
     * 启动模式
     * @var string
     */
    private $server = 'sw';

    /**
     * Air constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setRootDir($path);

        $this->registerExceptionHandler();

        $this->registerBaseBinds();

        $this->registerCoreAliases();

        $this->registerRuntimeService();
    }

    /**
     * 设定运行模式 sw ng
     * @param string $pattern
     * @return IServer
     */
    public function server(string $pattern = 'ng') : IServer
    {
        $this->server = $pattern;

        try {
            return $this->make($this->server);
        } catch (\Throwable $throwable) {
            die($throwable->getMessage().PHP_EOL);
        }
    }

    /**
     * 获取调度对象
     * @return Dispatcher
     */
    public function getDispatcher() : Dispatcher
    {
        $dispatcherClass = $this->getAlias('dispatcher');

        return new $dispatcherClass($this);
    }

    /**
     * 获取运行方式 返回 ng 或 sw
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * 框架版本
     * @return string
     */
    public function getVersion()
    {
        return static::VERSION;
    }

    /**
     * 设定项目根目录
     * @param $path
     * @return $this
     */
    public function setRootDir($path)
    {
        if (!$this->root) {
            $this->root = $path;
        }

        return $this;
    }

    /**
     * 获取项目根路径
     * @return string
     */
    public function getRootDirPath()
    {
        return $this->root.DIRECTORY_SEPARATOR;
    }

    /**
     * 获取app目录路径
     * @return string
     */
    public function getAppDirPath()
    {
        return $this->getRootDirPath().'app';
    }

    /**
     * 获取config目录路径
     * @return string
     */
    public function getConfigDirPath()
    {
        return $this->getRootDirPath().'config';
    }

    /**
     * 获取routes目录路径
     * @return string
     */
    public function getRoutesDirPath()
    {
        return $this->getRootDirPath().'routes';
    }

    /**
     * 获取runtime目录路径
     * @return string
     */
    public function getRuntimeDirPath()
    {
        return $this->getRootDirPath().'runtime';
    }

    /**
     * 获取logs目录路径
     * @return string
     */
    public function getLogsDirPath()
    {
        return $this->getRuntimeDirPath().DIRECTORY_SEPARATOR.'logs';
    }

    /**
     * 获取logs文件路径
     * @return string
     * @throws \Exception
     */
    public function getLogsFilePath()
    {
        return $this->getLogsDirPath().DIRECTORY_SEPARATOR.$this->make('config')->get('app.env').'.log';
    }

    /**
     * 绑定基础服务
     */
    private function registerBaseBinds()
    {
        static::setInstance($this);

        $this->instance(Container::class, $this);
        $this->instance(static::class, $this);
    }

    /**
     * 注册服务别名
     */
    private function registerCoreAliases()
    {
        foreach ([
             'app' => \Air\Kernel\Container\Container::class,
             'container' => \Air\Kernel\Container\Container::class,
             'dispatcher' => \Air\Kernel\Dispatcher\Dispatcher::class,
             'request' => \Air\Kernel\Transfer\Request::class,
             'response' => \Air\Kernel\Transfer\Response::class,
             'response.json' => \Air\Kernel\Transfer\JsonResponse::class,
             'route' => \Air\Kernel\Routing\Route::class,
             'route.dispatcher' => \Air\Kernel\Routing\RouteDispatcher::class,
             'router' => \Air\Kernel\Routing\Router::class,
             'pipeline' => \Air\Pipeline\Pipeline::class,
             'logger' => \Air\Log\Logger::class,
             'cache.apcu' => \Air\Cache\Apcu::class,
             'debug' => \Air\Kernel\Debug\IDebug::class
        ] as $key => $alias) {
            $this->alias($key, $alias);
        }
    }

    /**
     * 注册运行时服务
     */
    private function registerRuntimeService()
    {
        /**! Sw模式运行 !**/
        $this->singleton('sw', function () {
            return $this->make(Sw::class);
        });

        /**! Ng模式运行 !**/
        $this->singleton('ng', function () {
            return $this->make(Ng::class);
        });

        /**! 配置 !**/
        $this->singleton('config', function() {
            return $this->make(\Noodlehaus\Config::class, $this->getConfigDirPath());
        });

        /**! 日志 !**/
        $this->singleton('log', function () {
            return $this->make(
                Logger::class,
                new \Monolog\Logger($this->make('config')->get('app.env')),
                $this->getLogsFilePath(),
                $this->make('config')->get('log.level')
            );
        });

        /**! 缓存 !**/
        $this->singleton('cache', function () {
            return $this->make('cache.'.$this->make('config')->get('cache.drive'));
        });

        /**! Debug !**/
        $this->singleton(IDebug::class, function () {
            return $this->make($this->make('config')->get('debug.handler') ?? Debug::class);
        });

        /**! 路由 !**/
        $this->singleton(Router::class);
    }

    /**
     * 注册异常、错误处理
     */
    private function registerExceptionHandler()
    {
        error_reporting(-1);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * @param $level
     * @param $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @throws ErrorException
     */
    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * @param $e
     * @throws Exception
     */
    public function handleException($e)
    {
        if (!$e instanceof Exception) {
            $e = new FatalThrowableError($e);
        }

        try {
            $this->make('debug')->report($e);
        } catch (Exception $e) {}

        if ($this->getServer() === 'ng') {
            $this->make('debug')->render($this->make('request'), $e)->send();
        } else {
            /**! sw 中使用输出 !**/
            echo $e->getMessage();
        }
    }

    /**
     * @throws Exception
     */
    public function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalExceptionFromError($error));
        }
    }

    /**
     * @param array $error
     * @return ErrorException
     */
    private function fatalExceptionFromError(array $error)
    {
        return new ErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], null
        );
    }

    /**
     * @param $type
     * @return bool
     */
    private function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
}
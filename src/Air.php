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
        $this->setPath($path);

        $this->registerExceptionHandler();

        $this->registerBaseBinds();

        $this->registerCoreAliases();

        $this->registerBootService();
    }

    /**
     * 返回启动服务
     * @param string $pattern
     * @return IServer
     * @throws \Exception
     */
    public function server(string $pattern = 'ng') : IServer
    {
        $this->server = $pattern;

        return $this->make($this->server);
    }

    /**
     * @param Air|null $air
     * @return Dispatcher
     */
    public function getDispatcher(Air $air = null) : Dispatcher
    {
        $dispatcherClass = $this->getAlias('dispatcher');

        is_null($air) ? : $air->registerBaseBinds();

        return new $dispatcherClass($air ?? $this);
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
     * 返回 Air 框架版本
     * @return string
     */
    public function getVersion()
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
        return $this->getRootPath().'logs';
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
     * 绑定基础服务
     */
    public function registerBaseBinds()
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
     * 注册启动服务
     */
    private function registerBootService()
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
            return $this->make(\Noodlehaus\Config::class, $this->getConfigPath());
        });

        /**! 日志 !**/
        $this->singleton('log', function () {
            return $this->make(Logger::class);
        });

        /**! 缓存 !**/
        $this->singleton('cache', function () {
            return $this->make('cache.'.$this->make('config')->get('cache.drive'));
        });

        /**! Debug !**/
        $this->singleton(IDebug::class, function () {
            return $this->make($this->get('config')->get('app.debug_handler') ?? Debug::class);
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
            $this->getExceptionHandler()->report($e);
        } catch (Exception $e) {}

        $this->renderHttpResponse($e);
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
     * 输出错误
     * @param Exception $e
     * @throws \Exception
     */
    private function renderHttpResponse(Exception $e)
    {
        $this->getExceptionHandler()->render($this->get('request'), $e)->send();
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

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getExceptionHandler()
    {
        return $this->make(IDebug::class);
    }
}
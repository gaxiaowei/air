<?php
namespace Air;

use Air\Kernel\Container\Container;
use Air\Kernel\Debug\Exception\FatalThrowableError;
use Air\Kernel\Debug\IHandler;
use App\Exception\Handler;
use ErrorException;
use Exception;

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

        $this->registerExceptionHandler();

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

    /**
     * 注册异常、错误处理
     */
    private function registerExceptionHandler()
    {
        $this->singleton(IHandler::class, Handler::class);

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
     * @param Exception $e
     * @throws \Exception
     */
    protected function renderHttpResponse(Exception $e)
    {
        $this->getExceptionHandler()->render($this->get('request'), $e)->send();
    }

    /**
     * @param array $error
     * @return ErrorException
     */
    protected function fatalExceptionFromError(array $error)
    {
        return new ErrorException(
            $error['message'], $error['type'], 0, $error['file'], $error['line'], null
        );
    }

    /**
     * @param $type
     * @return bool
     */
    protected function isFatal($type)
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getExceptionHandler()
    {
        return $this->make(IHandler::class);
    }
}
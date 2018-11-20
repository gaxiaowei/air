<?php
namespace Air\Service\Server;

use Air\Air;
use Air\Kernel\InjectAir;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use App\Http\Kernel;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Server as TcpServer;
use Swoole\Http\Server as HttpServer;

class Sw implements IServer
{
    /**
     * @var TcpServer
     */
    private $sw = null;

    /**
     * @var Air
     */
    private $air;

    public function __construct(Air $air)
    {
        $this->air = $air;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        define('SW', true);

        $config = $this->getAir()->make('config');

        /**! 开启Http服务 !**/
        if ($config->get('sw.http.enable')) {
            $set = $config->get('sw.set');

            $this->sw = new HttpServer(
                $config->get('sw.http.bind'),
                $config->get('sw.http.port')
            );

            $this->sw->set($set);
            $this->sw->on('request', [$this, 'request']);
        }

        /**! 开启Tcp服务 !**/
        if ($config->get('sw.tcp.enable')) {
            $set = $this->getAir()->make($config->get('sw.tcp.pack'))->getProBufSet() ?? [];

            if (is_null($this->sw)) {
                $tcpPort = $this->sw = new TcpServer(
                    $config->get('sw.tcp.bind'),
                    $config->get('sw.tcp.port')
                );

                $set = $set + $config->get('sw.set');
            } else {
                $tcpPort = $this->sw->addListener(
                    $config->get('sw.tcp.bind'),
                    $config->get('sw.tcp.port'),
                    SWOOLE_SOCK_TCP
                );
            }

            $tcpPort->set($set);
            $tcpPort->on('connect',     [$this, 'connect']);
            $tcpPort->on('close',       [$this, 'close']);
            $tcpPort->on('receive',     [$this, 'receive']);
        }
        unset($config, $tcpPort, $set);

        $this->registerCommonCallback();

        $this->sw->start();
    }

    /**
     * @param TcpServer $server
     * @param $fd
     * @param $reactorId
     */
    public function connect(TcpServer $server, $fd, $reactorId)
    {

    }

    /**
     * 连接关闭
     * @param TcpServer $server
     * @param $fd
     */
    public function close(TcpServer $server, $fd)
    {

    }

    /**
     * tcp 请求处理
     * @param TcpServer $server
     * @param $fd
     * @param $reactorId
     * @param $data
     */
    public function receive(TcpServer $server, $fd, $reactorId, $data)
    {

    }

    /**
     * http 请求处理
     * @param SwRequest $request
     * @param SwResponse $response
     * @throws \Exception
     */
    public function request(SwRequest $request, SwResponse $response)
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        /**@var $httpKernel Kernel**/
        $httpKernel = new Kernel($this->getAir(), $this->getAir()->make('router'));

        /**! 处理http头字段大小写问题 !**/
        $server = array_change_key_case($request->server, CASE_UPPER);
        foreach ($request->header as $key => $val) {
            $server[sprintf('HTTP_%s', strtoupper(strtr($key, '-', '_')))] = $val;
        }

        /**@var $httpRequest \Air\Kernel\Logic\Handle\Request**/
        $httpRequest = new Request($request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $request->rawContent() ?? null
        );
        unset($server);

        /**@var $httpResponse Response**/
        $httpResponse = $httpKernel->handle($httpRequest);

        /**! response !**/
        $response->status($httpResponse->getStatusCode());
        foreach ($httpResponse->headers->allPreserveCase() as $key => $values) {
            foreach ($values as $val) {
                $response->header($key, $val);
            }
        }

        $response->end($httpResponse->getContent());
        $httpKernel->terminate($httpRequest, $httpResponse);
    }

    /**
     * 向task进程投递消息
     */
    public function task()
    {

    }

    /**
     * task进程向worker投递消息
     */
    public function finish()
    {

    }

    public function pipeMessage()
    {

    }

    /**
     * master 进程启动
     */
    public function start()
    {
        $this->setProcessName('php master process');
    }

    /**
     * master 进程关闭
     */
    public function shutdown()
    {

    }

    /**
     * manager 进程启动
     */
    public function managerStart()
    {
        $this->setProcessName('php manager process');
    }

    /**
     * manager 进程停止
     */
    public function managerStop()
    {

    }

    /**
     * worker 进程启动
     * @param TcpServer $server
     * @param $workerId
     *
     * @throws \Exception
     */
    public function workerStart(TcpServer $server, $workerId)
    {
        if (!$server->taskworker) {
            $this->setProcessName('php worker process');

            /**! 加载路由 !**/
            $this->getAir()->make('router')->group([], function ($router) {
                /**@var $router \Air\Kernel\Routing\Router**/

                $router
                    ->namespace('App\Http')
                    ->prefix('api')
                    ->group([], $this->getAir()->getRoutesPath().'/api.php');

                $router
                    ->namespace('App\Http')
                    ->prefix('rpc')
                    ->group([], $this->getAir()->getRoutesPath().'/rpc.php');
            });
        } else {
            $this->setProcessName('php task process');
        }
    }

    /**
     * worker 进程退出
     */
    public function workerStop()
    {

    }

    /**
     * worker 进程发生错误
     */
    public function workerError()
    {

    }

    /**
     * worker 进程退出
     */
    public function workerExit()
    {

    }

    private function setProcessName($name)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } else {
            swoole_set_process_name($name);
        }
    }

    private function registerCommonCallback()
    {
        if (!is_null($this->sw)) {
            $this->sw->on('start',    [$this, 'start']);
            $this->sw->on('shutdown', [$this, 'shutdown']);

            $this->sw->on('workerStart',  [$this, 'workerStart']);
            $this->sw->on('workerStop',   [$this, 'workerStop']);
            $this->sw->on('workerExit',   [$this, 'workerExit']);
            $this->sw->on('workerError',  [$this, 'workerError']);

            $this->sw->on('task',     [$this, 'task']);
            $this->sw->on('finish',   [$this, 'finish']);

            $this->sw->on('pipeMessage',  [$this, 'pipeMessage']);
            $this->sw->on('managerStart', [$this, 'managerStart']);
            $this->sw->on('managerStop',  [$this, 'managerStop']);
        }
    }

    public function getAir()
    {
        if ($this instanceof InjectAir) {
            return parent::getAir();
        }

        return $this->air;
    }
}
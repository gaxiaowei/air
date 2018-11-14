<?php
namespace Air\Service\Server;

use Air\Air;
use Air\Kernel\Logic\Handle\Request;
use Air\Kernel\Logic\Handle\Response;
use App\Http\Kernel;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Server as TcpServer;
use Swoole\Http\Server as HttpServer;

class Protocol
{
    /**
     * @var TcpServer
     */
    private $protocol = null;
    private $air = null;

    public function __construct(Air $air)
    {
        $this->air = $air;
    }

    /**
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function run()
    {
        $config = $this->air->make('config');

        /**! 开启Http服务 !**/
        if ($config->get('protocol.http.enable')) {
            $set = $config->get('server.set');

            $this->protocol = new HttpServer(
                $config->get('protocol.http.bind'),
                $config->get('protocol.http.port')
            );

            $this->protocol->set($set);
            $this->protocol->on('request', [$this, 'request']);
        }

        /**! 开启Tcp服务 !**/
        if ($config->get('protocol.tcp.enable')) {
            $set = $this->air->make($config->get('protocol.tcp.pack'))->getProBufSet() ?? [];

            if (is_null($this->protocol)) {
                $tcpPort = $this->protocol = new TcpServer(
                    $config->get('protocol.tcp.bind'),
                    $config->get('protocol.tcp.port')
                );

                $set = $set + $config->get('server.set');
            } else {
                $tcpPort = $this->protocol->addListener(
                    $config->get('protocol.tcp.bind'),
                    $config->get('protocol.tcp.port'),
                    SWOOLE_SOCK_TCP
                );
            }

            $tcpPort->set($set);
            $tcpPort->on('connect',     [$this, 'connect']);
            $tcpPort->on('close',       [$this, 'close']);
            $tcpPort->on('receive',     [$this, 'receive']);
        }
        unset($config, $tcpPort, $set);

        $this->registerCommonEvent();

        $this->protocol->start();
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
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function request(SwRequest $request, SwResponse $response)
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        /**@var $httpKernel Kernel**/
        $httpKernel = $this->air->make(Kernel::class);

        /**! 处理http头字段大小写问题 !**/
        $server = array_change_key_case($request->server, CASE_UPPER);
        foreach ($request->header as $key => $val) {
            $server[sprintf('HTTP_%s', strtoupper(strtr($key, '-', '_')))] = $val;
        }

        /**@var $httpRequest \Air\Kernel\Logic\Handle\Request**/
        $httpRequest = $this->air->make(Request::class, [
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $request->rawContent() ?? null
        ]);
        unset($server);

        /**@var $httpResponse Response**/
        $httpResponse = $httpKernel->handle($httpRequest);

        /**! response 响应!**/
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
     */
    public function workerStart(TcpServer $server, $workerId)
    {
        if (!$server->taskworker) {
            $this->setProcessName('php worker process');
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

    private function registerCommonEvent()
    {
        if (!is_null($this->protocol)) {
            $this->protocol->on('start',    [$this, 'start']);
            $this->protocol->on('shutdown', [$this, 'shutdown']);

            $this->protocol->on('workerStart',  [$this, 'workerStart']);
            $this->protocol->on('workerStop',   [$this, 'workerStop']);
            $this->protocol->on('workerExit',   [$this, 'workerExit']);
            $this->protocol->on('workerError',  [$this, 'workerError']);

            $this->protocol->on('task',     [$this, 'task']);
            $this->protocol->on('finish',   [$this, 'finish']);

            $this->protocol->on('pipeMessage',  [$this, 'pipeMessage']);
            $this->protocol->on('managerStart', [$this, 'managerStart']);
            $this->protocol->on('managerStop',  [$this, 'managerStop']);
        }
    }
}
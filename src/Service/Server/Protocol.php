<?php
namespace Air\Service\Server;

use Air\Air;
use App\Http\Kernel;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

class Protocol
{
    /**
     * @var Server
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

        /**! 开启Tcp服务 !**/
        if ($config->get('protocol.tcp.enable')) {
            $set = $config->get('server.set') +
                $this->air->make($config->get('protocol.tcp.pack'))->getProBufSet() ?? [];

            $this->protocol = new Server(
                $config->get('protocol.tcp.bind'),
                $config->get('protocol.tcp.port')
            );

            $this->protocol->set($set);

            $this->protocol->on('connect',     [$this, 'connect']);
            $this->protocol->on('close',       [$this, 'close']);
            $this->protocol->on('receive',     [$this, 'receive']);
            $this->protocol->on('packet',      [$this, 'packet']);
        }

        /**! 开启Http服务 !**/
        if ($config->get('protocol.http.enable')) {
            $set = ['open_http_protocol' => true];

            if (is_null($this->protocol)) {
                $http = $this->protocol = new Server(
                    $config->get('protocol.http.bind'),
                    $config->get('protocol.http.port')
                );

                $set = $config->get('server.set') + $set;
            } else {
                $http = $this->protocol->addListener(
                    $config->get('protocol.http.bind'),
                    $config->get('protocol.http.port'),
                    SWOOLE_SOCK_TCP
                );
            }

            $http->set($set);
            $http->on('request', [$this, 'request']);

            unset($http, $set, $config);
        }

        $this->registerCommonEvent();

        $this->protocol->start();
    }

    /**
     * @param Server $server
     * @param $fd
     * @param $reactorId
     */
    public function connect(Server $server, $fd, $reactorId)
    {

    }

    /**
     * 连接关闭
     * @param Server $server
     * @param $fd
     */
    public function close(Server $server, $fd)
    {

    }

    /**
     * Tcp 消息处理
     */
    public function receive()
    {

    }

    /**
     * UDP协议接收消息
     */
    public function packet()
    {

    }

    /**
     * http 请求处理
     * @param Request $request
     * @param Response $response
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function request(Request $request, Response $response)
    {
        if ($request->server['request_uri'] === '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        }

        /**@var $httpKernel Kernel**/
        $httpKernel = $this->air->make(Kernel::class);

        /**@var $httpRequest \Symfony\Component\HttpFoundation\Request**/
        $httpRequest = $this->air->make('request', [
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $request->server ?? [],
            $request->rawContent() ?? null
        ]);

        /**@var $httpResponse \Symfony\Component\HttpFoundation\Response**/
        $httpResponse = $httpKernel->handle($httpRequest);

        /** 发送header **/
        foreach ($httpResponse->headers->allPreserveCaseWithoutCookies() as $key => $val) {
            $response->header($key, array_pop($val));
        }

        /** 发送cookie **/
        foreach ($httpResponse->headers->getCookies() as $key => $val) {

        }

        $response->status($httpResponse->getStatusCode());
        $response->end($httpResponse->getContent());

        $httpKernel->terminate($httpRequest, $httpResponse);
        unset($httpKernel);

        var_dump($this->air);
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
     * @param Server $server
     * @param $workerId
     * @throws \Air\Kernel\Container\Exception\BindingResolutionException
     * @throws \Air\Kernel\Container\Exception\EntryNotFoundException
     */
    public function workerStart(Server $server, $workerId)
    {
        if (!$server->taskworker) {
            $this->setProcessName('php worker process');

            if (true === $this->air->make('config')->get('protocol.http.enable')) {
                $this->air->singleton(Kernel::class);
            }
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
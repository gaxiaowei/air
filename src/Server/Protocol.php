<?php
namespace Air\Server;

use Air\Air;
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

    public function run()
    {
        $config = $this->air->make('config');

        /**! 开启Http服务 !**/
        if ($config->get('protocol.http.enable')) {
            $this->protocol = new \Swoole\Http\Server(
                $config->get('protocol.http.bind'),
                $config->get('protocol.http.port')
            );

            $this->protocol->set($config->get('server.set'));
            $this->protocol->on('request',     [$this, 'request']);
        }

        /**! 开启Tcp服务 !**/
        if ($config->get('protocol.tcp.enable')) {
            $set = $config->get('server.set') + $this->air->make($config->get('protocol.tcp.pack'))->getProBufSet();

            if (is_null($this->protocol)) {
                $this->protocol = new Server(
                    $config->get('protocol.tcp.bind'),
                    $config->get('protocol.tcp.port')
                );

                $this->protocol->set($set);

                $event = $this->protocol;
            } else {
                $post = $this->protocol->addListener(
                    $config->get('protocol.tcp.bind'),
                    $config->get('protocol.tcp.port'),
                    SWOOLE_SOCK_TCP
                );

                $post->set($set);

                $event = $post;
            }

            $event->on('connect',     [$this, 'connect']);
            $event->on('close',       [$this, 'close']);
            $event->on('receive',     [$this, 'receive']);
            $event->on('packet',      [$this, 'packet']);

            unset($set, $event);
        }

        $this->registerCommonEvent();
        $this->protocol->start();
    }

    /**
     * 新的连接
     * @param Server $server
     * @param $fd
     */
    public function connect(Server $server, $fd)
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
     * http请求处理
     * @param Request $request
     * @param Response $response
     */
    public function request(Request $request, Response $response)
    {
        $response->end('hello world');
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
     */
    public function workerStart(Server $server)
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
            $this->protocol->on('start', [$this, 'start']);
            $this->protocol->on('shutdown', [$this, 'shutdown']);

            $this->protocol->on('workerStart', [$this, 'workerStart']);
            $this->protocol->on('workerStop', [$this, 'workerStop']);
            $this->protocol->on('workerExit', [$this, 'workerExit']);
            $this->protocol->on('workerError', [$this, 'workerError']);

            $this->protocol->on('task',        [$this, 'task']);
            $this->protocol->on('finish',      [$this, 'finish']);

            $this->protocol->on('pipeMessage', [$this, 'pipeMessage']);
            $this->protocol->on('managerStart',[$this, 'managerStart']);
            $this->protocol->on('managerStop', [$this, 'managerStop']);
        }
    }
}
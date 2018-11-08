<?php
namespace Air\Server;

use Air\Kernel\Container\Container;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

class Tcp
{
    private $tcpServer = null;
    private $container = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function run()
    {
        $this->tcpServer = new Server('0.0.0.0', 8000);

        $this->tcpServer->set([
            'worker_num' => 3,
            'task_worker_num' => 1,
            'open_http_protocol' => 1
        ]);

        $this->tcpServer->on('start',       [$this, 'start']);
        $this->tcpServer->on('shutdown',    [$this, 'shutdown']);

        $this->tcpServer->on('workerStart', [$this, 'workerStart']);
        $this->tcpServer->on('workerStop',  [$this, 'workerStop']);
        $this->tcpServer->on('workerExit',  [$this, 'workerExit']);
        $this->tcpServer->on('workerError', [$this, 'workerError']);

        $this->tcpServer->on('connect', [$this, 'connect']);
        $this->tcpServer->on('close',   [$this, 'close']);

        $this->tcpServer->on('receive', [$this, 'receive']);
        $this->tcpServer->on('packet',  [$this, 'packet']);
        $this->tcpServer->on('request',     [$this, 'request']);

        $this->tcpServer->on('task',        [$this, 'task']);
        $this->tcpServer->on('finish',      [$this, 'finish']);

        $this->tcpServer->on('pipeMessage', [$this, 'pipeMessage']);

        $this->tcpServer->on('managerStart',[$this, 'managerStart']);
        $this->tcpServer->on('managerStop', [$this, 'managerStop']);

        $this->tcpServer->start();
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
        $this->setProcessName('php master process ('.FILE.')');
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
}
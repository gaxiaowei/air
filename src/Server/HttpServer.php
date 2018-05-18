<?php
namespace Air\Server;

use Air\Kernel\Container\Container;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class HttpServer
{
        private $di;
        private $httpServer = null;
        private $events = ['start', 'request'];

        public function __construct(Container $di)
        {
                $this->di = $di;
                $this->httpServer = new Server('0.0.0.0', 8088);
                $this->httpServer->set([
                        //'upload_tmp_dir' => '/tmp'
                ]);

                foreach ($this->events as $event) {
                        $this->httpServer->on($event, [$this, 'on'.ucfirst($event)]);
                }
        }

        /**
         * 启动http服务
         */
        public function start()
        {
                $this->httpServer->start();
        }

        /**
         * http启动回调
         */
        public function onStart()
        {
                var_dump($this->di);
                echo 'http server started success';
        }

        /**
         * 处理请求
         * @param Request $request
         * @param Response $response
         */
        public function onRequest(Request $request, Response $response)
        {
                $response->end('Hello World！');
                print_r($request);
        }
}
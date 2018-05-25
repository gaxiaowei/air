<?php
class WebSocketServer
{
        private $ws;
        private $events = [];
        private $sockets = [];

        public function __construct()
        {
                $this->ws = new Swoole\WebSocket\Server("0:0:0:0", 8777);

                $this->ws->on('open', [$this, 'open']);
                $this->ws->on('message', [$this, 'message']);
                $this->ws->on('close', [$this, 'close']);

        }

        public function start()
        {
                $this->ws->start();
        }

        public function on($event, Closure $closure)
        {
                $this->events[$event] = $closure;

                return $this;
        }

        public function open(Swoole\WebSocket\Server $server, $req)
        {

        }

        public function message(Swoole\WebSocket\Server $server, $frame)
        {

                //$server->push($frame->fd, json_encode(["hello", "world"]));
        }

        public function close(Swoole\WebSocket\Server $server, $fd)
        {
                echo "connection close: {$fd}\n";
        }
}

(new WebSocketServer())
        ->on('connection', function($socket) {
                $socket->emit('news', json_encode(['hello' => 'world']));
                $socket->on('unread', function ($data) {
                        echo $data;
                });
        })
        ->start();

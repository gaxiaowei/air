<?php
namespace Air\Service\Client;

use Air\Air;
use Air\Kernel\Transfer\Request;

class RPC
{
    public static function call(string $rpc, $params = [], Request $request = null)
    {
        static::connect();
    }

    /**
     * @throws \Exception
     */
    private static function connect()
    {
        print_r(Air::getInstance()->get('config')->all());
    }
}
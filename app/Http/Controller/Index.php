<?php
namespace App\Http\Controller;

use Air\Kernel\Transfer\Request;
use Air\Service\Client\RPC;

class Index
{
    public function show(Request $request)
    {
        echo '<pre>';
        RPC::call('abc');

        var_dump($request->server);
    }
}
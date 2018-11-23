<?php
namespace App\Http\Controller;

use Air\Kernel\Logic\Handle\Request;

class Index
{
    public function show(Request $request)
    {
        var_dump($request);

        return 'Hello World';
    }
}
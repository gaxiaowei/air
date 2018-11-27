<?php
namespace App\Http\Controller;

use Air\Kernel\Transfer\Request;

class Index
{
    public function show(Request $request)
    {
        return 'Hello World';
    }
}
<?php
namespace Air\Kernel\Routing;

use Air\Air;
use Air\Kernel\Logic\Handle\Request;

class RouterDispatch
{
    private $air;

    public function __construct(Air $air)
    {
        $this->air = $air;
    }

    public function run(Request $request)
    {
        $router = $this->air->make('router');
    }
}
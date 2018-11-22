<?php
namespace Air\Kernel\Debug;

use Air\Kernel\Logic\Handle\Request;
use Exception;

interface IHandler
{
    /**
     * 导出或写入日志
     * @param Exception $e
     * @return mixed
     */
    public function report(Exception $e);

    /**
     * 将异常渲染到页面
     * @param Request $request
     * @param Exception $e
     * @return mixed
     */
    public function render(Request $request, Exception $e);
}

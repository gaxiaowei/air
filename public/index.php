<?php
require __DIR__.'/../vendor/autoload.php';

class Demo
{
        private $di;

        public function __construct(\Air\Kernel\Container\Container $di)
        {
                $this->di = $di;
        }

        public function get()
        {
                var_dump($this->di);
        }
}

class A
{
        private $di;
        public function __construct(\Air\Kernel\Container\Container $di)
        {
                $this->di = $di;
        }
}

try {
        $di = \Air\Kernel\Container\Container::getInstance();

        /**@var $demo Demo**/
        $di->singleton(Demo::class);
        $demo = $di->make(Demo::class);
        $demo->get();

        /**@var $a A**/
        $a = $di->make(A::class);
} catch (Exception $e) {

}
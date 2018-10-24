<?php
namespace Air;

use Air\Kernel\Container\Container;
use Air\Kernel\Loader\ClassLoader;

class Air extends Container
{
    /**
     * Air 版本
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Air 安装路径
     * @var string
     */
    private $root;

    /**
     * Air constructor.
     * @param string $path
     */
    public function __construct($path = '')
    {
        $this->setPath($path);

        $this->registerBaseBinds();
    }

    /**
     * 返回 Air 框架版本
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->root = $path;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getComposerPath()
    {
        $path = $this->root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'composer';
        if (!file_exists($path)) {
            throw new \Exception('The composer is not initialized');
        }

        return $path;
    }

    /**
     * 向Container注册基础绑定
     * @return void
     */
    private function registerBaseBinds()
    {
        static::setInstance($this);

        $this->instance(Container::class, $this);
        $this->alias('app', Container::class);

        $this->singleton(ClassLoader::class);
    }

    /**
     * 注册自动加载类方法
     */
    private function registerClassLoader()
    {
        /**@var $classLoader \Air\Kernel\Loader\ClassLoader*/
        $classLoader = $this->make(ClassLoader::class);

        $composerPath = $this->getComposerPath();

        if (file_exists($path = $composerPath.DIRECTORY_SEPARATOR.'autoload_namespaces.php')) {
            $psr0 = $classLoader->requireFile($path);
            if ($psr0) {
                foreach ($psr0 as $prefix => $dirs) {
                    $classLoader->setPrefixPsr0($prefix, $dirs);
                }
            }

            unset($psr0);
        }

        if (file_exists($path = $composerPath.DIRECTORY_SEPARATOR.'autoload_psr4.php')) {
            $psr4 = $classLoader->requireFile($path);
            if ($psr4) {
                foreach ($psr4 as $prefix => $dirs) {
                    $classLoader->setPrefixPsr4($prefix, $dirs);
                }
            }

            unset($psr4);
        }

        if (file_exists($path = $composerPath.DIRECTORY_SEPARATOR.'autoload_files.php')) {
            $files = $classLoader->requireFile($path);
            if ($files) {
                foreach ($files as $filePath) {
                    $classLoader->requireFile($filePath);
                }
            }

            unset($files, $path, $filePath);
        }
    }
}
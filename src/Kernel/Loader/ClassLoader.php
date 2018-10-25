<?php
namespace Air\Kernel\Loader;

/**
 * 实现类自动加载
 * Class ClassLoader
 * @package Air\Kernel\Loader
 */
class ClassLoader
{
    private $prefixPsr4 = [];
    private $fallbackPsr4Dirs = [];

    private $prefixPsr0 = [];
    private $fallbackPsr0Dirs = [];

    public function getPrefixPsr4(): array
    {
        return $this->prefixPsr4;
    }

    public function setPrefixPsr4($prefix, $paths = null)
    {
        if (($prefix && is_null($paths)) || (!$prefix && !is_null($paths))) {
            $paths = array_merge(
                is_null($paths) ? [] : (array)$paths,
                $prefix ? (array)$prefix : []
            );

            foreach ($paths as $dir) {
                if (!in_array($dir, $this->fallbackPsr4Dirs)) {
                    $this->fallbackPsr4Dirs[] = $dir;
                }
            }

            return;
        }

        $prefix = rtrim($prefix, '\\');
        $paths = (array)$paths;

        foreach ($paths as $path) {
            $this->prefixPsr4[$prefix{0}][$prefix][$path] = strlen($prefix);
        }
    }

    public function getPrefixPsr0(): array
    {
        return $this->prefixPsr0;
    }

    public function setPrefixPsr0($prefix, $paths = null)
    {
        if (($prefix && is_null($paths)) || (!$prefix && !is_null($paths))) {
            $paths = array_merge(
                is_null($paths) ? [] : (array)$paths,
                $prefix ? (array)$prefix : []
            );

            foreach ($paths as $dir) {
                if (!in_array($dir, $this->fallbackPsr0Dirs)) {
                    $this->fallbackPsr0Dirs[] = $dir;
                }
            }

            return;
        }

        $prefix = rtrim($prefix, '\\');
        $paths = (array)$paths;

        foreach ($paths as $path) {
            $this->prefixPsr0[$prefix{0}][$prefix] = array_merge(
                $this->prefixPsr0[$prefix{0}][$prefix] ?? [],
                [$path]
            );
        }
    }

    public function register($prepend = false)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    public function loadClass($class)
    {
        $class = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
        $first = $class{0};

        //PSR-4
        if (isset($this->prefixPsr4[$first])) {
            foreach ($this->prefixPsr4[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir => $length) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . substr($class, $length))) {
                            $this->includeFile($file);

                            return true;
                        }
                    }
                }
            }
        }

        //PSR-4 fallback dirs
        foreach ($this->fallbackPsr4Dirs as $dir) {
            if (is_file($file = $dir . DIRECTORY_SEPARATOR . $class)) {
                $this->includeFile($file);

                return true;
            }
        }

        //PSR-0 lookup
        if (false !== $pos = strrpos($class, '\\')) {
            $class = substr($class, 0, $pos + 1).
                strtr(substr($class, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            // PEAR-like class name
            $class = strtr($class, '_', DIRECTORY_SEPARATOR);
        }

        //PSR-0
        if (isset($this->prefixPsr0[$first])) {
            foreach ($this->prefixPsr0[$first] as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . $class)) {
                            $this->includeFile($file);

                            return true;
                        }
                    }
                }
            }
        }

        //PSR-0 fallback dirs
        foreach ($this->fallbackPsr0Dirs as $dir) {
            if (is_file($file = $dir . DIRECTORY_SEPARATOR . $class)) {
                $this->includeFile($file);

                return true;
            }
        }

        return false;
    }

    public function includeFile(string $path, $once = false)
    {
        if ($once) {
            return include_once $path;
        }

        return include $path;
    }

    public function requireFile(string $path, $once = false)
    {
        if ($once) {
            return require_once $path;
        }

        return require $path;
    }
}
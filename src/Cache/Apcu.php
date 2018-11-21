<?php
namespace Air\Cache;

use Air\Air;

class Apcu implements ICache
{
    use CacheTrait;

    /**
     * Apcu constructor.
     * @param Air $air
     * @throws \Exception
     */
    public function __construct(Air $air)
    {
        $config = $air->make('config');

        $this->prefix =
            $config->get('app.name').':'.$config->get('app.env').
            ($config->get('cache.prefix') ? ':'.$config->get('cache.prefix') : '');
    }

    /**
     * 获取某个缓存值
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $key = $this->getFullKey($key);

        if ($this->hasCache($key)) {
            return $this->getCache($key);
        }

        return apcu_fetch($key);
    }

    /**
     * 设置一个缓存
     * @param string $key
     * @param $value
     * @param int|null $ttl
     * @return bool
     */
    public function set(string $key, $value, int $ttl = null) : bool
    {
        $key = $this->getFullKey($key);

        $this->delCache($key);

        return apcu_store($key, $value, $ttl ?? 0);
    }

    /**
     * 检查某个缓存是否存在
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return apcu_exists($this->getFullKey($key));
    }

    /**
     * 删除某个缓存
     * @param string $key
     * @return bool
     */
    public function del(string $key): bool
    {
        $this->delCache($key);

        return apcu_delete($this->getFullKey($key));
    }

    /**
     * 获取所有缓存
     * @return array
     */
    public function all() : array
    {
        $cache = [];
        foreach ($this->getApcuIterator($this->prefix) as $item) {
            $cache[$item['key']] = $item['value'];
        }

        return $cache;
    }

    /**
     * 设置某个缓存的过期时间
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl): bool
    {
        if ($this->has($key)) {
            return $this->set($key, $this->get($key), $ttl);
        }

        return false;
    }

    /**
     * 查看某个缓存自动过期时间
     * @param string $key
     * @return int
     */
    public function ttl(string $key) : int
    {
        if ($this->has($key)) {
            $cache = $this->getApcuIterator($key)->current();
            echo '<pre>';
            print_r($cache);
            if (false !== $cache) {
                if ($cache['ttl'] === 0) {
                    return -1;
                }

                $ttl = $cache['ttl'] - (time() - $cache['creation_time']);

                return $ttl > 0 ? $ttl : 0;
            }
        }

        return 0;
    }

    /**
     * 清空所有緩存
     * @return bool
     */
    public function flush() : bool
    {
        return apcu_delete($this->getApcuIterator($this->prefix));
    }

    /**
     * @param string $key
     * @param int $format
     * @return \APCUIterator
     */
    private function getApcuIterator(string $key, $format = APC_ITER_ALL) : \APCUIterator
    {
        $key = strtr($this->getFullKey($key), [
            '*' => '\*', '.' => '\.', '?' => '\?', '+' => '\+', '$' => '\$', '^' => '\^',
            '[' => '\[', ']' => '\]', '(' => '\(', ')' => '\)', '{' => '\{', '}' => '\}',
            '|' => '\|', '/' => '\/', '\\' => '\\\\'
        ]);

        return new \APCUIterator("/^{$key}/", $format);
    }
}
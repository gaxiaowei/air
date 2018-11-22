<?php
namespace Air\Cache;

trait CTrait
{
    /**
     * 前缀
     * @var string
     */
    private $prefix;

    /**
     * 程序缓存
     * @var array
     */
    private $caches = [];

    /**
     * 獲取某個key的全名称
     * @param string $key
     * @return string
     */
    public function getFullKey(string $key) : string
    {
        if ($key === $this->prefix) {
            return $key;
        }

        return $this->prefix.':'.$key;
    }

    /**
     * 判断程序中是否存在缓存
     * @param string $key
     * @return bool
     */
    public function hasCache(string $key) : bool
    {
        return isset($this->caches[$key]);
    }

    /**
     * 从程序缓存中获取值
     * @param string $key
     * @return bool
     */
    public function getCache(string $key) : bool
    {
        return $this->caches[$key];
    }

    /**
     * 删除程序缓存
     * @param string $key
     * @return bool
     */
    public function delCache(string $key) : bool
    {
        unset($this->caches[$key]);

        return true;
    }
}
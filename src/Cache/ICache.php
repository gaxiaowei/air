<?php
namespace Air\Cache;

interface ICache
{
    public function get(string $key);
    public function set(string $key, $value, int $ttl = null) : bool;

    public function has(string $key) : bool;
    public function del(string $key) : bool;

    public function all() : array;

    public function expire(string $key, int $ttl) : bool;
    public function ttl(string $key) : int;

    public function hasCache(string $key) : bool;
    public function delCache(string $key) : bool;
    public function getCache(string $key);

    public function flush() : bool;

    public function getFullKey(string $key) : string;
}
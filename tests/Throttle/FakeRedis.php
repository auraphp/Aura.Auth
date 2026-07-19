<?php
namespace Aura\Auth\Throttle;

/**
 * A minimal in-memory stand-in for a phpredis/Predis client, covering just the
 * commands RedisThrottleStorage uses. Values are stored as strings to mirror
 * Redis, which returns all hash fields as strings.
 */
class FakeRedis
{
    public $hashes = array();

    public $expires = array();

    public function hincrby($key, $field, $increment)
    {
        $current = isset($this->hashes[$key][$field])
            ? (int) $this->hashes[$key][$field]
            : 0;
        $new = $current + (int) $increment;
        $this->hashes[$key][$field] = (string) $new;
        return $new;
    }

    public function hset($key, $field, $value)
    {
        $existed = isset($this->hashes[$key][$field]);
        $this->hashes[$key][$field] = (string) $value;
        return $existed ? 0 : 1;
    }

    public function expire($key, $ttl)
    {
        $this->expires[$key] = (int) $ttl;
        return true;
    }

    public function hgetall($key)
    {
        return isset($this->hashes[$key]) ? $this->hashes[$key] : array();
    }

    public function del($key)
    {
        $existed = isset($this->hashes[$key]);
        unset($this->hashes[$key], $this->expires[$key]);
        return $existed ? 1 : 0;
    }
}

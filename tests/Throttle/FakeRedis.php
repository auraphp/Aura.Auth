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

    /**
     * Command names that should answer false, standing in for the replies
     * phpredis returns by value rather than by exception (WRONGTYPE and the
     * like).
     */
    public $fail = array();

    /** Mirrors phpredis::getLastError(). */
    public $last_error;

    public function getLastError()
    {
        return $this->last_error;
    }

    protected function fails($command)
    {
        return in_array($command, $this->fail, true);
    }

    public function hincrby($key, $field, $increment)
    {
        if ($this->fails('hincrby')) {
            return false;
        }

        $current = isset($this->hashes[$key][$field])
            ? (int) $this->hashes[$key][$field]
            : 0;
        $new = $current + (int) $increment;
        $this->hashes[$key][$field] = (string) $new;
        return $new;
    }

    public function hset($key, $field, $value)
    {
        if ($this->fails('hset')) {
            return false;
        }

        $existed = isset($this->hashes[$key][$field]);
        $this->hashes[$key][$field] = (string) $value;
        return $existed ? 0 : 1;
    }

    public function expire($key, $ttl)
    {
        // Redis answers false for a key that does not exist.
        if ($this->fails('expire')) {
            return false;
        }

        $this->expires[$key] = (int) $ttl;
        return true;
    }

    public function hgetall($key)
    {
        if ($this->fails('hgetall')) {
            return false;
        }

        return isset($this->hashes[$key]) ? $this->hashes[$key] : array();
    }

    public function del($key)
    {
        $existed = isset($this->hashes[$key]);
        unset($this->hashes[$key], $this->expires[$key]);
        return $existed ? 1 : 0;
    }
}

<?php
namespace Aura\Auth\Throttle;

/**
 * An in-memory RedisClientInterface implementation for tests. Values are stored
 * as strings to mirror Redis, which returns all hash fields as strings.
 */
class FakeRedisClient implements RedisClientInterface
{
    public $hashes = array();

    public $expires = array();

    public function hashIncrement(string $key, string $field, int $by): int
    {
        $current = isset($this->hashes[$key][$field])
            ? (int) $this->hashes[$key][$field]
            : 0;
        $new = $current + $by;
        $this->hashes[$key][$field] = (string) $new;
        return $new;
    }

    public function hashSet(string $key, string $field, string $value): void
    {
        $this->hashes[$key][$field] = $value;
    }

    public function expire(string $key, int $ttl): void
    {
        $this->expires[$key] = $ttl;
    }

    public function hashGetAll(string $key): array
    {
        return isset($this->hashes[$key]) ? $this->hashes[$key] : array();
    }

    public function delete(string $key): void
    {
        unset($this->hashes[$key], $this->expires[$key]);
    }
}

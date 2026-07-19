<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Throttle;

/**
 *
 * Adapts a native Redis client to {@see RedisClientInterface}.
 *
 * Wraps either a phpredis `\Redis` or a `Predis\Client`. Both expose the
 * commands used here under case-insensitive method names (`hincrby`, `hset`,
 * `expire`, `hgetall`, `del`), so a single adapter serves both. A client that
 * does not — a different library, a cluster wrapper — should implement
 * {@see RedisClientInterface} directly instead of being passed here.
 *
 * @package Aura.Auth
 *
 */
class NativeRedisClient implements RedisClientInterface
{
    /**
     *
     * The wrapped native Redis client (phpredis `\Redis` or `Predis\Client`).
     *
     * @var object
     *
     */
    protected $client;

    /**
     *
     * Constructor.
     *
     * @param object $client A phpredis `\Redis` or `Predis\Client`.
     *
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function hashIncrement(string $key, string $field, int $by): int
    {
        return (int) $this->client->hincrby($key, $field, $by);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function hashSet(string $key, string $field, string $value): void
    {
        $this->client->hset($key, $field, $value);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function expire(string $key, int $ttl): void
    {
        $this->client->expire($key, $ttl);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function hashGetAll(string $key): array
    {
        $data = $this->client->hgetall($key);
        return is_array($data) ? $data : array();
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function delete(string $key): void
    {
        $this->client->del($key);
    }
}

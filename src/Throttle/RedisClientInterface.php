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
 * The minimal Redis operations {@see RedisThrottleStorage} needs, expressed in
 * neutral terms so any Redis client library can back it.
 *
 * The shipped {@see NativeRedisClient} adapts a phpredis `\Redis` or a
 * `Predis\Client`. A client whose method names or signatures differ (a custom
 * wrapper, a cluster client, a mock) is supported by implementing this
 * interface directly, without touching the storage class.
 *
 * @package Aura.Auth
 *
 */
interface RedisClientInterface
{
    /**
     *
     * Increments a hash field by an amount, creating the key and field as
     * needed (Redis HINCRBY).
     *
     * @param string $key The key holding the hash.
     *
     * @param string $field The hash field to increment.
     *
     * @param int $by The amount to add.
     *
     * @return int The field value after the increment.
     *
     */
    public function hashIncrement(string $key, string $field, int $by): int;

    /**
     *
     * Sets a hash field to a value (Redis HSET).
     *
     * @param string $key The key holding the hash.
     *
     * @param string $field The hash field to set.
     *
     * @param string $value The value to store.
     *
     * @return void
     *
     */
    public function hashSet(string $key, string $field, string $value): void;

    /**
     *
     * Sets the time-to-live on a key, in seconds (Redis EXPIRE).
     *
     * @param string $key The key.
     *
     * @param int $ttl The lifetime in seconds.
     *
     * @return void
     *
     */
    public function expire(string $key, int $ttl): void;

    /**
     *
     * Returns all fields and values of a hash as an associative array of
     * strings, or an empty array when the key does not exist (Redis HGETALL).
     *
     * An implementation that cannot read the key must throw rather than answer
     * an empty array. The caller cannot tell the two apart, and reads the
     * empty array as "this key has no recorded failures" -- so a backend that
     * masks its own errors silently switches throttling off for that key.
     *
     * @param string $key The key holding the hash.
     *
     * @return array The hash contents.
     *
     * @throws \Aura\Auth\Exception when the hash cannot be read.
     *
     */
    public function hashGetAll(string $key): array;

    /**
     *
     * Deletes a key (Redis DEL).
     *
     * @param string $key The key to delete.
     *
     * @return void
     *
     */
    public function delete(string $key): void;
}

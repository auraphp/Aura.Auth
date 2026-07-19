<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Throttle;

/**
 *
 * Stores failed-login counters in Redis, one hash per key with native TTL.
 *
 * Each key maps to a Redis hash holding `count` and `last` fields. Every
 * recorded failure increments `count`, stamps `last`, and (re)sets the key's
 * expiry to `$window` seconds. Because the expiry is refreshed on each failure,
 * the window is measured from the *most recent* failure: a persistent attacker
 * stays counted, while a key goes away automatically once `$window` seconds
 * pass with no further failures. This differs slightly from the row-based
 * {@see PdoThrottleStorage}, whose window slides per individual failure; both
 * are appropriate for throttling.
 *
 * The client is reached through {@see RedisClientInterface}, so any Redis
 * library can back it. Pass a {@see NativeRedisClient} to wrap a phpredis
 * `\Redis` or `Predis\Client`, or implement the interface for a client whose
 * methods differ.
 *
 * @package Aura.Auth
 *
 */
class RedisThrottleStorage implements ThrottleStorageInterface
{
    /**
     *
     * The Redis client.
     *
     * @var RedisClientInterface
     *
     */
    protected $client;

    /**
     *
     * A prefix applied to every key, to namespace throttle data.
     *
     * @var string
     *
     */
    protected $prefix;

    /**
     *
     * How long, in seconds, a failure is remembered (the key TTL).
     *
     * @var int
     *
     */
    protected $window;

    /**
     *
     * Constructor.
     *
     * @param RedisClientInterface $client A Redis client (for example, a
     * NativeRedisClient wrapping phpredis or Predis).
     *
     * @param string $prefix A key prefix namespacing throttle data.
     *
     * @param int $window How long a failure is remembered, in seconds (default
     * 15 minutes).
     *
     */
    public function __construct(RedisClientInterface $client, $prefix = 'aura_auth_throttle:', $window = 900)
    {
        $this->client = $client;
        $this->prefix = $prefix;
        $this->window = (int) $window;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function recordFailure(string $key): void
    {
        $redis_key = $this->prefix . $key;
        $this->client->hashIncrement($redis_key, 'count', 1);
        $this->client->hashSet($redis_key, 'last', (string) time());
        $this->client->expire($redis_key, $this->window);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function getFailures(string $key): array
    {
        $data = $this->client->hashGetAll($this->prefix . $key);
        if (empty($data)) {
            return array('count' => 0, 'last' => null);
        }

        return array(
            'count' => isset($data['count']) ? (int) $data['count'] : 0,
            'last' => isset($data['last']) ? (int) $data['last'] : null,
        );
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function reset(string $key): void
    {
        $this->client->delete($this->prefix . $key);
    }

    /**
     *
     * {@inheritDoc}
     *
     * Redis expires keys natively via the per-key TTL, so there is nothing to
     * prune here.
     *
     */
    public function deleteExpired(): void
    {
        // no-op: native TTL handles expiry
    }
}

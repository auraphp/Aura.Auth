<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Throttle;

use Aura\Auth\Exception\ConnectionFailed;

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
 * FAILURE HANDLING. A throttle backend that quietly reports "no failures
 * recorded" when it is actually broken stops being a throttle: the counter
 * reads zero, every attempt is allowed, and nothing says so. So a reply that
 * signals command failure is raised as {@see ConnectionFailed} rather than
 * folded into a benign-looking zero. That matches {@see PdoThrottleStorage},
 * which lets PDO's own exceptions propagate, and it fails closed --
 * ThrottleService::assert() runs before the wrapped adapter, so a broken
 * backend blocks login attempts instead of waving them through.
 *
 * This is a narrower hazard than it first appears. phpredis throws
 * RedisException of its own accord when the connection is refused or drops
 * mid-session, so an unreachable Redis already failed closed before these
 * guards; Predis throws for server errors too. What is left is the case
 * phpredis reports by return value instead of by exception -- principally
 * WRONGTYPE, when a key under the throttle prefix holds something other than a
 * hash because another application shares the database. Untreated, that
 * disables throttling for the affected key permanently: the read returns
 * nothing *and* the write silently does nothing, so it never heals.
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
        $count = $this->client->hincrby($key, $field, $by);

        // HINCRBY otherwise always answers the new counter value, so false is
        // failure and nothing else; casting it would record the attempt as
        // count 0 -- an uncounted failure.
        $this->check($count, 'hincrby', $key);

        return (int) $count;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function hashSet(string $key, string $field, string $value): void
    {
        // HSET answers 1 for a new field and 0 for an updated one; both are
        // success. A dropped 'last' stamp leaves ThrottleService computing
        // backoff from a null timestamp, which reads as "no wait required".
        $this->check($this->client->hset($key, $field, $value), 'hset', $key);
    }

    /**
     *
     * {@inheritDoc}
     *
     * Deliberately unchecked: EXPIRE answers false for a key that does not
     * exist, which is an ordinary outcome here (the key can be reset by a
     * successful login, or expire on its own, between the increment and this
     * call). Treating that as failure would throw on a healthy system.
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

        // A missing key is an empty array, not false, so there is no benign
        // reading of a non-array reply: it means the counter could not be
        // read, which must not be reported as a counter of zero.
        if (! is_array($data)) {
            throw new ConnectionFailed(
                "Redis hgetall failed for key '{$key}'" . $this->lastError() . ';'
                    . ' refusing to report the failure count as zero'
            );
        }

        return $data;
    }

    /**
     *
     * {@inheritDoc}
     *
     * Deliberately unchecked: DEL answers 0 when there was nothing to delete,
     * an ordinary outcome. A silently failed delete also errs the safe way --
     * the counter survives a successful login, throttling that account rather
     * than exempting it -- and this runs on the success path, where throwing
     * would turn a good login into an error.
     *
     */
    public function delete(string $key): void
    {
        $this->client->del($key);
    }

    /**
     *
     * Raises a reply that signals command failure.
     *
     * Only an exact `false` counts. The commands guarded here answer an
     * integer on every success, including the zeroes that mean "field already
     * existed" or "nothing to delete", so a loose check would throw on healthy
     * traffic. Clients that report failure by throwing -- phpredis for
     * connection loss, Predis for server errors -- never reach this and
     * propagate their own exception instead.
     *
     * @param mixed $reply The client's reply.
     *
     * @param string $command The command name, for the message.
     *
     * @param string $key The key it was issued against.
     *
     * @return void
     *
     * @throws ConnectionFailed when the reply signals failure.
     *
     */
    protected function check($reply, string $command, string $key): void
    {
        if ($reply !== false) {
            return;
        }

        throw new ConnectionFailed(
            "Redis {$command} failed for key '{$key}'" . $this->lastError() . ';'
                . ' the failed attempt could not be counted'
        );
    }

    /**
     *
     * Returns the server's own explanation of the last failure, ready to
     * append to a message, or an empty string when the client does not offer
     * one.
     *
     * This is the part an operator can act on: `WRONGTYPE Operation against a
     * key holding the wrong kind of value` names the actual fault, where the
     * key alone only says something went wrong. phpredis reports it through
     * getLastError(); Predis raises its own exception instead and never
     * reaches here, so the method is absent there and the detail is dropped.
     *
     * @return string
     *
     */
    protected function lastError(): string
    {
        if (! method_exists($this->client, 'getLastError')) {
            return '';
        }

        $error = $this->client->getLastError();

        return $error ? ": {$error}" : '';
    }
}

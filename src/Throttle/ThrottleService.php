<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Throttle;

use Aura\Auth\Exception\ThrottleExceeded;

/**
 *
 * Brute-force login throttling policy.
 *
 * Counts failed attempts per key (in this library, the attempted user name) via
 * a {@see ThrottleStorageInterface}, and applies exponential backoff once the
 * count passes a threshold. The first `max_attempts` failures are free; after
 * that, attempt N must wait `min(2 ^ (count - max_attempts), cap)` seconds since
 * the most recent failure. A real user is only briefly delayed, while a flood is
 * slowed to a crawl — and because the counter keys on the user name, no account
 * is ever fully locked out.
 *
 * @package Aura.Auth
 *
 */
class ThrottleService
{
    /**
     *
     * The failure-counter storage.
     *
     * @var ThrottleStorageInterface
     *
     */
    protected $storage;

    /**
     *
     * How many failures are allowed before backoff begins.
     *
     * @var int
     *
     */
    protected $max_attempts;

    /**
     *
     * The maximum backoff, in seconds.
     *
     * @var int
     *
     */
    protected $cap;

    /**
     *
     * Constructor.
     *
     * @param ThrottleStorageInterface $storage The failure-counter storage.
     *
     * @param array $options Policy options: `max_attempts` (failures allowed
     * before backoff, default 5) and `cap` (maximum backoff in seconds, default
     * 15 minutes).
     *
     */
    public function __construct(ThrottleStorageInterface $storage, array $options = array())
    {
        $this->storage = $storage;
        $this->max_attempts = isset($options['max_attempts'])
            ? (int) $options['max_attempts']
            : 5;
        $this->cap = isset($options['cap'])
            ? (int) $options['cap']
            : 900;
    }

    /**
     *
     * Asserts that an attempt for a key is currently allowed, throwing if the
     * caller is still within a backoff period.
     *
     * @param string $key The throttle key (for example, the attempted user
     * name).
     *
     * @return void
     *
     * @throws ThrottleExceeded when the caller must wait before retrying.
     *
     */
    public function assert(string $key): void
    {
        $state = $this->storage->getFailures($key);
        $count = $state['count'];

        if ($count <= $this->max_attempts) {
            return;
        }

        $wait = min(2 ** ($count - $this->max_attempts), $this->cap);
        $remaining = $wait - (time() - $state['last']);

        if ($remaining > 0) {
            throw ThrottleExceeded::afterSeconds($remaining);
        }
    }

    /**
     *
     * Records a failed attempt for a key.
     *
     * @param string $key The throttle key.
     *
     * @return void
     *
     */
    public function recordFailure(string $key): void
    {
        $this->storage->recordFailure($key);
    }

    /**
     *
     * Clears the failure counter for a key (for example, after a successful
     * login).
     *
     * @param string $key The throttle key.
     *
     * @return void
     *
     */
    public function reset(string $key): void
    {
        $this->storage->reset($key);
    }

    /**
     *
     * Housekeeping: drops failure records older than the storage window.
     *
     * @return void
     *
     */
    public function deleteExpired(): void
    {
        $this->storage->deleteExpired();
    }
}

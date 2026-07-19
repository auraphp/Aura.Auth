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
 * Server-side storage for failed-login counters, used to throttle brute-force
 * attempts.
 *
 * A throttle "key" identifies what is being counted (in this library, the
 * attempted user name). Implementations remember each failure for a bounded
 * *window* of time; failures older than that window no longer count. The policy
 * decision — how many failures trigger a delay and how long to wait — lives in
 * the throttle service, not here. This interface only records and reports raw
 * failure state.
 *
 * @package Aura.Auth
 *
 */
interface ThrottleStorageInterface
{
    /**
     *
     * Records a single failed attempt for a key at the current time.
     *
     * @param string $key The throttle key (for example, the attempted user
     * name).
     *
     * @return void
     *
     */
    public function recordFailure(string $key): void;

    /**
     *
     * Reports the recent failure state for a key.
     *
     * Returns an associative array with:
     *
     * - `count` (int) the number of failures within the window
     * - `last` (int|null) the Unix time of the most recent failure, or null if
     *   there are none
     *
     * @param string $key The throttle key.
     *
     * @return array The failure state.
     *
     */
    public function getFailures(string $key): array;

    /**
     *
     * Clears all recorded failures for a key (for example, after a successful
     * login).
     *
     * @param string $key The throttle key.
     *
     * @return void
     *
     */
    public function reset(string $key): void;

    /**
     *
     * Housekeeping: drops failure records older than the window. Backends with
     * native key expiry (such as Redis TTL) may implement this as a no-op.
     *
     * @return void
     *
     */
    public function deleteExpired(): void;
}

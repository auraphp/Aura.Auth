<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Exception;

use Aura\Auth\Exception;

/**
 *
 * Thrown when too many failed login attempts have been made for a key and the
 * caller must wait before trying again.
 *
 * Carries the number of seconds remaining before the next attempt is allowed,
 * so the application can tell the user when to retry.
 *
 * @package Aura.Auth
 *
 */
class ThrottleExceeded extends Exception
{
    /**
     *
     * Seconds the caller must wait before the next attempt.
     *
     * @var int
     *
     */
    protected $seconds_remaining = 0;

    /**
     *
     * Named constructor: build the exception from a wait time.
     *
     * @param int $seconds Seconds remaining before the next attempt is allowed.
     *
     * @return self
     *
     */
    public static function afterSeconds(int $seconds): self
    {
        $e = new self(
            "Too many failed login attempts; try again in {$seconds} seconds."
        );
        $e->seconds_remaining = $seconds;
        return $e;
    }

    /**
     *
     * Returns the number of seconds the caller must wait.
     *
     * @return int
     *
     */
    public function getSecondsRemaining(): int
    {
        return $this->seconds_remaining;
    }
}

<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Exception;

use Aura\Auth\Exception;

/**
 *
 * Thrown when a presented API token is genuine but has passed its expiry.
 *
 * This is deliberately the ONLY verification failure that is distinguishable
 * from the others: it is raised only after the validator has been matched, so
 * reaching it proves the caller holds the real token and learns nothing new.
 * Unknown selectors and mismatched validators both yield a plain null instead,
 * so that they cannot be told apart and used to enumerate tokens.
 *
 * Carries the expiry time, so the application can tell the client how stale
 * the token is and prompt for a new one.
 *
 * @package Aura.Auth
 *
 */
class TokenExpired extends Exception
{
    /**
     *
     * The Unix time at which the token expired.
     *
     * @var int
     *
     */
    protected $expires = 0;

    /**
     *
     * Named constructor: build the exception from an expiry time.
     *
     * @param int $expires The Unix time at which the token expired.
     *
     * @return self
     *
     */
    public static function at(int $expires): self
    {
        $e = new self(
            "The API token expired at " . gmdate('Y-m-d H:i:s', $expires) . " UTC."
        );
        $e->expires = $expires;
        return $e;
    }

    /**
     *
     * Returns the Unix time at which the token expired.
     *
     * @return int
     *
     */
    public function getExpires(): int
    {
        return $this->expires;
    }
}

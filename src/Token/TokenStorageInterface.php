<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Token;

/**
 *
 * Server-side storage for API authentication tokens.
 *
 * Implementations persist a token as a `selector` (a public lookup key) and a
 * `hashed_validator` (the SHA-256 hash of the secret validator). The raw
 * validator is never stored; only its hash is kept, so that a leak of the
 * storage backend does not by itself yield usable tokens.
 *
 * Note that this deliberately has no rotation method, unlike
 * {@see \Aura\Auth\Remember\RememberStorageInterface}. A remember-me cookie
 * rotates its validator on every use so that a stolen cookie is single-use;
 * doing the same to an API token would break concurrent requests, because the
 * first request to arrive would invalidate the token the others are still
 * presenting. API tokens are instead revoked explicitly, or by expiry.
 *
 * A stored token row is an associative array with the keys:
 *
 * - `selector` (string) the public lookup key
 * - `hashed_validator` (string) the SHA-256 hash of the secret validator
 * - `username` (string) the user the token authenticates as
 * - `userdata` (array) arbitrary application data carried with the token; the
 *   library stores and returns it without interpreting it. Applications that
 *   restrict what a token may do typically keep their scopes here. Note that
 *   such scopes can only ever *narrow* what the user is already permitted to
 *   do; they never grant anything on their own.
 * - `label` (string|null) a human-readable name for the token, e.g. "CI deploy"
 * - `expires` (int) the Unix time at which the token expires
 * - `created_at` (int) the Unix time at which the token was issued
 * - `last_used_at` (int|null) the Unix time the token was last presented, or
 *   null if never used or if the implementation does not track it
 *
 * @package Aura.Auth
 *
 */
interface TokenStorageInterface
{
    /**
     *
     * Persists a new token.
     *
     * @param string $selector The public lookup key.
     *
     * @param string $hashed_validator The SHA-256 hash of the secret validator.
     *
     * @param string $username The user the token authenticates as.
     *
     * @param array $userdata Arbitrary application data to carry with the token.
     *
     * @param int $expires The Unix time at which the token expires.
     *
     * @param int $created_at The Unix time at which the token was issued.
     *
     * @param string|null $label A human-readable name for the token.
     *
     * @return void
     *
     */
    public function create(
        $selector,
        $hashed_validator,
        $username,
        array $userdata,
        $expires,
        $created_at,
        $label = null
    ): void;

    /**
     *
     * Finds a token by its selector.
     *
     * @param string $selector The public lookup key.
     *
     * @return array|null The token row, or null if not found.
     *
     */
    public function findBySelector($selector): ?array;

    /**
     *
     * Records that a token was just used, if the implementation tracks it.
     *
     * Implementations that do not track last-use are expected to make this a
     * no-op, so that callers may invoke it unconditionally.
     *
     * @param string $selector The public lookup key.
     *
     * @param int $now The Unix time at which the token was used.
     *
     * @return void
     *
     */
    public function touch($selector, $now): void;

    /**
     *
     * Deletes a token by its selector (revokes a single token).
     *
     * @param string $selector The public lookup key.
     *
     * @return void
     *
     */
    public function deleteBySelector($selector): void;

    /**
     *
     * Deletes all tokens for a user name (e.g. "revoke all my tokens").
     *
     * @param string $username The user name.
     *
     * @return void
     *
     */
    public function deleteByUsername($username): void;

    /**
     *
     * Deletes all expired tokens (housekeeping).
     *
     * @return void
     *
     */
    public function deleteExpired(): void;
}

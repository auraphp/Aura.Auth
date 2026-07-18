<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Remember;

/**
 *
 * Server-side storage for "remember me" tokens.
 *
 * Implementations persist a token as a `selector` (a public lookup key) and a
 * `hashed_validator` (the SHA-256 hash of the secret validator). The raw
 * validator is never stored; only its hash is kept so that a leak of the
 * storage backend does not by itself yield usable cookies.
 *
 * A stored token row is an associative array with the keys:
 *
 * - `selector` (string) the public lookup key
 * - `hashed_validator` (string) the SHA-256 hash of the secret validator
 * - `username` (string) the remembered user name
 * - `userdata` (array) arbitrary remembered user data
 * - `expires` (int) the Unix time at which the token expires
 *
 * @package Aura.Auth
 *
 */
interface RememberStorageInterface
{
    /**
     *
     * Persists a new token.
     *
     * @param string $selector The public lookup key.
     *
     * @param string $hashed_validator The SHA-256 hash of the secret validator.
     *
     * @param string $username The remembered user name.
     *
     * @param array $userdata Arbitrary remembered user data.
     *
     * @param int $expires The Unix time at which the token expires.
     *
     * @return null
     *
     */
    public function create($selector, $hashed_validator, $username, array $userdata, $expires);

    /**
     *
     * Finds a token by its selector.
     *
     * @param string $selector The public lookup key.
     *
     * @return array|null The token row, or null if not found.
     *
     */
    public function findBySelector($selector);

    /**
     *
     * Rotates the validator and expiry for an existing token.
     *
     * @param string $selector The public lookup key.
     *
     * @param string $hashed_validator The new SHA-256 hash of the validator.
     *
     * @param int $expires The new Unix expiry time.
     *
     * @return null
     *
     */
    public function update($selector, $hashed_validator, $expires);

    /**
     *
     * Deletes a token by its selector.
     *
     * @param string $selector The public lookup key.
     *
     * @return null
     *
     */
    public function deleteBySelector($selector);

    /**
     *
     * Deletes all tokens for a user name (e.g. "log out everywhere").
     *
     * @param string $username The remembered user name.
     *
     * @return null
     *
     */
    public function deleteByUsername($username);

    /**
     *
     * Deletes all expired tokens (housekeeping).
     *
     * @return null
     *
     */
    public function deleteExpired();
}

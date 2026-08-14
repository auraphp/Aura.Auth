<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Token;

use Aura\Auth\Phpfunc;

/**
 *
 * A split-token ("selector : validator") value object and helper.
 *
 * A token value is `selector:validator`. The `selector` is a public lookup
 * key; the `validator` is a secret compared in constant time against a stored
 * SHA-256 hash. This is the scheme described at
 * https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
 *
 * The scheme is transport-agnostic: "remember me" carries the value in a
 * cookie, while API authentication carries it in a request header.
 *
 * @package Aura.Auth
 *
 */
class SplitToken
{
    /**
     *
     * A proxy for PHP functions (for testability of random_bytes()).
     *
     * @var Phpfunc
     *
     */
    protected $phpfunc;

    /**
     *
     * Constructor.
     *
     * @param Phpfunc $phpfunc A proxy for PHP functions.
     *
     */
    public function __construct(Phpfunc $phpfunc)
    {
        $this->phpfunc = $phpfunc;
    }

    /**
     *
     * Generates a new random selector.
     *
     * @return string A 16-character hex string (8 random bytes).
     *
     */
    public function newSelector(): string
    {
        return bin2hex($this->phpfunc->random_bytes(8));
    }

    /**
     *
     * Generates a new random validator.
     *
     * @return string A 64-character hex string (32 random bytes).
     *
     */
    public function newValidator(): string
    {
        return bin2hex($this->phpfunc->random_bytes(32));
    }

    /**
     *
     * Hashes a validator for storage.
     *
     * @param string $validator The secret validator.
     *
     * @return string The SHA-256 hash of the validator.
     *
     */
    public function hash(#[\SensitiveParameter] $validator): string
    {
        return hash('sha256', $validator);
    }

    /**
     *
     * Builds the token value from a selector and validator.
     *
     * @param string $selector The public lookup key.
     *
     * @param string $validator The secret validator.
     *
     * @return string The `selector:validator` token value.
     *
     */
    public function makeValue($selector, #[\SensitiveParameter] $validator): string
    {
        return $selector . ':' . $validator;
    }

    /**
     *
     * Parses a `selector:validator` token value.
     *
     * @param string $value The raw token value.
     *
     * @return array|null An array with `selector` and `validator` keys, or null
     * if the value is malformed.
     *
     */
    public function parseValue(#[\SensitiveParameter] $value): ?array
    {
        if (! is_string($value) || strpos($value, ':') === false) {
            return null;
        }

        list($selector, $validator) = explode(':', $value, 2);

        if ($selector === '' || $validator === '') {
            return null;
        }

        return array(
            'selector' => $selector,
            'validator' => $validator,
        );
    }

    /**
     *
     * Verifies a validator against a stored hash, in constant time.
     *
     * @param string $hashed_validator The stored SHA-256 hash.
     *
     * @param string $validator The validator presented by the client.
     *
     * @return bool
     *
     */
    public function verify($hashed_validator, #[\SensitiveParameter] $validator): bool
    {
        return hash_equals($hashed_validator, $this->hash($validator));
    }
}

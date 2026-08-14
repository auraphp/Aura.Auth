<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Verifier;

/**
 *
 * Password Verifier
 *
 * @package Aura.Auth
 *
 */
interface VerifierInterface
{
    /**
     *
     * Verify that a plaintext password matches a hashed one.
     *
     * @param string $plaintext Plaintext password. Marked
     * `#[\SensitiveParameter]` so that it is redacted from stack traces;
     * implementations should carry the attribute too, as PHP does not inherit
     * it from the interface.
     *
     * @param string $hashvalue Hashed password.
     *
     * @param array $extra Optional array of data.
     *
     * @return bool
     *
     */
    public function verify(
        #[\SensitiveParameter] $plaintext,
        $hashvalue,
        array $extra = array()
    ): bool;
}

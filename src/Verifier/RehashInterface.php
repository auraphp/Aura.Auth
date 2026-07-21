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
 * A verifier that can report when a stored hash is out of date and should be
 * replaced -- because the algorithm changed, because its cost no longer meets
 * the configured policy, or because it predates password_hash() entirely.
 *
 * This is separate from VerifierInterface so that existing implementations of
 * that interface keep working unchanged; adapters check for it with
 * `instanceof` and skip the report when a verifier does not provide one.
 *
 * @package Aura.Auth
 *
 */
interface RehashInterface
{
    /**
     *
     * Does this stored hash need to be replaced?
     *
     * Only meaningful for a hash that has just verified successfully: the
     * caller has the plaintext at that moment, which is the only time it can
     * produce a replacement.
     *
     * @param string $hashvalue The stored hash that was verified against.
     *
     * @param array $extra Optional extra data, as passed to verify().
     *
     * @return bool
     *
     */
    public function needsRehash($hashvalue, array $extra = array()): bool;
}

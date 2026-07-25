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
 * A verifier that can supply a throwaway hash in the format it actually reads,
 * so that an adapter's "no such username" path costs the same as a real failed
 * verification.
 *
 * WHY THE VERIFIER AND NOT THE ADAPTER. AbstractAdapter carries bcrypt dummy
 * hashes and picks between them by PHP version. That is right only when the
 * stored hashes are bcrypt at PHP's default cost. A verifier configured for a
 * legacy `hash()` algorithm, or an htpasswd file holding `$apr1$` or `{SHA}`
 * entries, verifies in microseconds -- against which a bcrypt dummy does not
 * equalise the two paths, it inverts them: the unknown username becomes the
 * slow one, by a wider margin than the leak being closed. Only the verifier
 * knows which format it reads, so only the verifier can produce a dummy that
 * costs the same as the real thing.
 *
 * This is separate from VerifierInterface so that existing implementations of
 * that interface keep working unchanged; AbstractAdapter checks for it with
 * `instanceof` and falls back to its own constants when a verifier does not
 * provide one.
 *
 * Implementations must satisfy two rules, the same ones documented on
 * AbstractAdapter::DUMMY_HASH_COST_10:
 *
 * - The value must be a *valid* hash of the format returned. A malformed one is
 *   typically rejected without any hashing work, which silently reinstates the
 *   timing leak this exists to close.
 *
 * - Its plaintext must be unknown, so that the value can never work as a
 *   password if it is ever copied into a password column.
 *
 * Generating it from random bytes at construction or on first use satisfies
 * both. Implementations should memoise it: producing a fresh hash on every call
 * doubles the cost of the failure path, which reintroduces a difference in the
 * other direction.
 *
 * @package Aura.Auth
 *
 */
interface DummyHashInterface
{
    /**
     *
     * Returns a hash to run a throwaway verification against, in the same
     * format and at the same cost as the hashes this verifier reads.
     *
     * The result of verifying against it is always discarded; it can never
     * authenticate anyone.
     *
     * @return string
     *
     */
    public function getDummyHash(): string;
}

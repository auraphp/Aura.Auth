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
 * Htaccess password Verifier
 *
 * @package Aura.Auth
 *
 */
class PasswordVerifier implements VerifierInterface, RehashInterface, DummyHashInterface
{
    /**
     *
     * A memoised throwaway hash in this verifier's own format, for the
     * "no such username" path; see getDummyHash().
     *
     * @var string|null
     *
     */
    protected $dummy_hash;

    /**
     *
     * The hashing algorithm to use.
     *
     * @var string|int
     *
     */
    protected $algo;

    /**
     *
     * Options for the algorithm, as passed to password_hash(); used to decide
     * whether a stored hash still meets the policy.
     *
     * @var array
     *
     */
    protected $options;

    /**
     *
     * Constructor.
     *
     * @param string|int $algo The hashing algorithm to use.
     *
     * @param array $options Options for the algorithm, matching those the
     * application passes to password_hash() -- `array('cost' => 12)`, say.
     * Only used by needsRehash(), so that raising the cost is detected as
     * well as changing the algorithm.
     *
     */
    public function __construct($algo, array $options = array())
    {
        $this->algo = $algo;
        $this->options = $options;
    }

    /**
     *
     * Verifies a password against a hash.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue The comparison hash.
     *
     * @param array $extra Optional array if used by verify
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = array()): bool
    {
        if ($this->isLegacyAlgo() && ! $this->isPasswordHash($hashvalue)) {
            return hash_equals($hashvalue, hash($this->algo, $plaintext));
        }

        return password_verify($plaintext, $hashvalue);
    }

    /**
     *
     * Does this stored hash need to be replaced?
     *
     * @param string $hashvalue The stored hash that was verified against.
     *
     * @param array $extra Optional extra data, as passed to verify().
     *
     * @return bool
     *
     */
    public function needsRehash($hashvalue, array $extra = array()): bool
    {
        if (! $this->isPasswordHash($hashvalue)) {
            // an unsalted hash() digest should always be replaced with real
            // password_hash() output; see docs/adapters.md
            return true;
        }

        if ($this->isLegacyAlgo()) {
            // already migrated past the legacy algorithm this verifier was
            // configured with; there is nothing better to move it to
            return false;
        }

        return password_needs_rehash($hashvalue, $this->algo, $this->options);
    }

    /**
     *
     * Returns a throwaway hash in this verifier's own format, so that an
     * adapter's "no such username" path costs what a real failed verification
     * costs.
     *
     * It is built from the configured algorithm and options, which is what
     * makes it match: a verifier set to argon2id produces an argon2id dummy, a
     * bcrypt one at cost 13 produces a cost-13 dummy, and one set to a legacy
     * `hash()` algorithm produces a plain digest that verifies as fast as the
     * real ones do. The adapter's own bcrypt constants are only a fallback for
     * verifiers that cannot answer this.
     *
     * The plaintext is random and never recorded, so nothing verifies against
     * the result. It is memoised because generating a fresh one per call would
     * double the cost of the failure path and reopen the difference from the
     * other side.
     *
     * @return string
     *
     */
    public function getDummyHash(): string
    {
        if ($this->dummy_hash !== null) {
            return $this->dummy_hash;
        }

        $unknown = bin2hex(random_bytes(32));

        if ($this->isLegacyAlgo()) {
            // a plain digest, so verify() takes the same hash_equals() path at
            // the same cost as a stored legacy hash would
            $this->dummy_hash = hash($this->algo, $unknown);
            return $this->dummy_hash;
        }

        $this->dummy_hash = password_hash($unknown, $this->algo, $this->options);

        return $this->dummy_hash;
    }

    /**
     *
     * Is the algorithm a plain hash() algorithm, rather than one of the
     * algorithms password_hash() knows?
     *
     * The PASSWORD_* constants have been strings since PHP 7.4 -- bcrypt is
     * "2y", argon2id is "argon2id" -- so a string algo is not by itself a
     * legacy one. Asking password_algos() keeps every current and future
     * password_hash() algorithm on the password_verify() path; testing
     * against PASSWORD_BCRYPT alone sent argon2 to hash(), where it raised a
     * ValueError.
     *
     * @return bool
     *
     */
    protected function isLegacyAlgo(): bool
    {
        return is_string($this->algo)
            && ! in_array($this->algo, password_algos(), true);
    }

    /**
     *
     * Is this stored value password_hash() output, rather than a plain digest?
     *
     * A verifier configured with a legacy algorithm still has to accept hashes
     * that have already been migrated, because the two kinds coexist in the
     * column for as long as the migration takes: the accounts that have logged
     * in since it started hold password_hash() output, the rest still hold the
     * old digest. Deciding from the configured algorithm alone would verify
     * every migrated account against the wrong scheme and lock its owner out
     * on their next visit.
     *
     * @param string $hashvalue The stored hash.
     *
     * @return bool
     *
     */
    protected function isPasswordHash($hashvalue): bool
    {
        return password_get_info((string) $hashvalue)['algo'] !== null;
    }
}

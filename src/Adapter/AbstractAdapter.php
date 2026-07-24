<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\Exception;
use Aura\Auth\Status;
use Aura\Auth\Auth;
use Aura\Auth\Verifier\VerifierInterface;
use Aura\Auth\Verifier\RehashInterface;
use Aura\Auth\Verifier\DummyHashInterface;
use Aura\Auth\Rehash\RehashStorageInterface;

/**
 *
 * Authentication adapter
 *
 * @package Aura.Auth
 *
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     *
     * A bcrypt hash used only to give a verifier real work to do when no
     * account matched, so that a missing username costs the same as a wrong
     * password. See verifyDummy().
     *
     * Two properties matter if this is ever regenerated:
     *
     * - It must be a *valid* hash. password_verify() rejects a malformed one
     *   immediately without hashing, which silently reinstates the timing
     *   leak this exists to close.
     *
     * - Its plaintext must be unknown. It was generated from random_bytes()
     *   and the plaintext was never recorded, so no password verifies against
     *   it. Never replace it with a hash of a known string: this value is
     *   public, and a user who copied it into a password column would then be
     *   left with a publicly-known password.
     *
     * There is one per bcrypt cost that PHP has defaulted to across the
     * versions we support; getDummyHash() picks between them.
     *
     * This value is public and documented on purpose: the protection is that
     * both login paths take the same time, which knowing the constant does not
     * undo. See docs/security.md.
     *
     * @const string
     *
     */
    const DUMMY_HASH_COST_10 = '$2y$10$eOcbNJ/6To/ATaEeIwlMm.YlDTw.XGRDPrRtEvyV7lckM2FyMpAHu';

    /**
     *
     * The cost-12 counterpart of DUMMY_HASH_COST_10; the same rules apply.
     *
     * @const string
     *
     */
    const DUMMY_HASH_COST_12 = '$2y$12$.t3MDSiflgKiN0m1.KrJJO3cemWCVIi2SUHtKjC24jTMt0XNqL7/6';

    /**
     *
     * Verifies a set of credentials against a storage backend.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     */
    abstract public function login(array $input): array;

    /**
     *
     * Handle logout logic against the storage backend.
     *
     * @param Auth $auth The authentication obbject to be logged out.
     *
     * @param string $status The new authentication status after logout.
     *
     * @return void
     *
     * @see Status
     *
     */
    public function logout(Auth $auth, $status = Status::ANON): void
    {
        // do nothing
    }

    /**
     *
     * Handle a resumed session against the storage backend.
     *
     * @param Auth $auth The authentication object to be resumed.
     *
     * @return void
     *
     */
    public function resume(Auth $auth): void
    {
        // do nothing
    }

    /**
     *
     * Did the hash that just verified need replacing?
     *
     * @var bool
     *
     */
    protected $needs_rehash = false;

    /**
     *
     * Did the most recent successful login verify against a stored hash that
     * should be replaced -- because the algorithm or cost policy has moved on,
     * or because it predates password_hash()?
     *
     * Only meaningful immediately after a successful login() on this adapter
     * instance, which is the only moment the application still holds the
     * plaintext needed to produce a replacement. It is reset at the start of
     * every login(), and is false when no rehash is needed, when the verifier
     * does not implement RehashInterface, or when login() did not succeed.
     *
     * @return bool
     *
     */
    public function needsRehash(): bool
    {
        return $this->needs_rehash;
    }

    /**
     *
     * An optional writer that replaces outdated hashes.
     *
     * @var RehashStorageInterface|null
     *
     */
    protected $rehash_storage;

    /**
     *
     * Whatever the last rehash attempt threw, if it failed.
     *
     * @var \Throwable|null
     *
     */
    protected $rehash_error;

    /**
     *
     * Sets a writer to replace outdated hashes automatically on successful
     * login, instead of leaving the caller to act on needsRehash().
     *
     * @param RehashStorageInterface $rehash_storage The writer.
     *
     * @return void
     *
     */
    public function setRehashStorage(RehashStorageInterface $rehash_storage): void
    {
        $this->rehash_storage = $rehash_storage;
    }

    /**
     *
     * Returns whatever the last automatic rehash threw, or null if it did not
     * run or did not fail.
     *
     * A rehash is housekeeping: failing it must not fail an otherwise good
     * login, so the exception is captured rather than propagated. It is worth
     * logging, though -- a store that rejects every rehash means the migration
     * is silently going nowhere.
     *
     * @return \Throwable|null
     *
     */
    public function getRehashError(): ?\Throwable
    {
        return $this->rehash_error;
    }

    /**
     *
     * Replaces the stored hash, if one is needed and a writer was given.
     *
     * Clears the needs-rehash flag on success, so that a caller doing both --
     * a writer wired up *and* its own needsRehash() block -- does not write
     * twice.
     *
     * @param string $username The account that just authenticated.
     *
     * @param string $plaintext The verified plaintext password.
     *
     * @return void
     *
     */
    protected function applyRehash($username, $plaintext): void
    {
        if (! $this->needs_rehash || ! $this->rehash_storage) {
            return;
        }

        try {
            $this->rehash_storage->rehash($username, $plaintext);
            $this->needs_rehash = false;
        } catch (\Throwable $e) {
            $this->rehash_error = $e;
        }
    }

    /**
     *
     * Records whether the verified hash should be replaced, if the verifier is
     * able to say.
     *
     * @param VerifierInterface $verifier The verifier that just succeeded.
     *
     * @param string $hashvalue The stored hash it verified against.
     *
     * @param array $extra Optional extra data, as passed to verify().
     *
     * @return void
     *
     */
    protected function setNeedsRehash(
        VerifierInterface $verifier,
        $hashvalue,
        array $extra = array()
    ): void {
        $this->needs_rehash = $verifier instanceof RehashInterface
            && $verifier->needsRehash($hashvalue, $extra);
    }

    /**
     *
     * Check the credential input for completeness.
     *
     * @param array $input
     *
     * @return void
     *
     */
    protected function checkInput(array $input): void
    {
        if (empty($input['username'])) {
            throw new Exception\UsernameMissing;
        }

        if (empty($input['password'])) {
            throw new Exception\PasswordMissing;
        }
    }

    /**
     *
     * Runs a throwaway verification against a dummy hash so that a username
     * with no matching account costs about as much as one with a wrong
     * password; without it, response time reveals which usernames exist.
     *
     * The dummy comes from the verifier when it implements
     * DummyHashInterface, and from getDummyHash() otherwise. The verifier is
     * preferred because it is the only party that knows which format it
     * actually reads: a bcrypt dummy equalises the two paths only for a
     * verifier that reads bcrypt. Against a legacy `hash()` digest or an
     * `$apr1$`/`{SHA}` htpasswd entry -- both verified in microseconds -- it
     * inverts the leak rather than closing it, making the unknown username the
     * slow answer by a wider margin than the original bug. See
     * docs/security.md.
     *
     * The result is deliberately discarded. This can never authenticate
     * anyone: callers use it only on a path that already failed, and must
     * still throw.
     *
     * @param VerifierInterface $verifier The verifier to keep busy.
     *
     * @param string $password The password supplied by the user.
     *
     * @return void
     *
     */
    protected function verifyDummy(VerifierInterface $verifier, $password): void
    {
        $hashvalue = $verifier instanceof DummyHashInterface
            ? $verifier->getDummyHash()
            : $this->getDummyHash();

        $verifier->verify($password, $hashvalue);
    }

    /**
     *
     * Returns the fallback dummy hash to verify against, matching the bcrypt
     * cost that this PHP version produces by default: PHP 8.4 raised the
     * default for PASSWORD_BCRYPT from 10 to 12.
     *
     * This is consulted only when the verifier does not implement
     * DummyHashInterface. Both stock verifiers do, so overriding this affects
     * a custom verifier only; to pin the cost with PasswordVerifier, pass the
     * cost to the verifier itself -- `new PasswordVerifier(PASSWORD_BCRYPT,
     * array('cost' => 13))` -- and its dummy follows automatically.
     *
     * The version is only a proxy for what actually matters, which is the
     * cost of the hashes already in storage. Accounts created under an older
     * default keep their original cost until rehashed, so a deployment with
     * long-lived hashes, or one that sets cost explicitly, can override this
     * to return a hash generated at the cost it really uses. Any replacement
     * must satisfy the rules on DUMMY_HASH_COST_10: a valid bcrypt hash whose
     * plaintext nobody knows. See docs/security.md.
     *
     * @return string
     *
     */
    protected function getDummyHash(): string
    {
        if (PHP_VERSION_ID >= 80400) {
            return static::DUMMY_HASH_COST_12;
        }

        return static::DUMMY_HASH_COST_10;
    }
}

<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Rehash;

/**
 *
 * Replaces the stored password hash for a user.
 *
 * Wiring one of these into an adapter turns password migration from something
 * the application has to remember to do after every successful login into
 * something that happens on its own. It is optional: without it, an adapter
 * only reports the condition through `needsRehash()`.
 *
 * The application supplies the implementation because only the application
 * knows its schema. An adapter is built from a SELECT specification -- columns,
 * a FROM that may join several tables, an optional WHERE -- which is not enough
 * to derive a safe UPDATE from.
 *
 * @package Aura.Auth
 *
 */
interface RehashStorageInterface
{
    /**
     *
     * Stores a new hash of the password for this user.
     *
     * Called only after the password has been verified successfully, so the
     * plaintext is known to be correct for this account. The implementation
     * chooses the algorithm to hash it with: that is deliberately *not* the
     * algorithm the adapter's verifier uses, because the usual reason to
     * rehash is migrating away from that algorithm -- verifying an old MD5
     * digest while storing bcrypt.
     *
     * Failures should be raised; the adapter catches them so that a failed
     * rehash cannot turn a successful login into an error.
     *
     * @param string $username The account whose hash to replace.
     *
     * @param string $plaintext The verified plaintext password. Marked
     * `#[\SensitiveParameter]` so that it is redacted from stack traces;
     * implementations should carry the attribute too, as PHP does not inherit
     * it from the interface.
     *
     * @return void
     *
     */
    public function rehash($username, #[\SensitiveParameter] $plaintext): void;
}

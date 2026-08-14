<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Token;

use Aura\Auth\Exception\TokenExpired;

/**
 *
 * Issues and verifies opaque API tokens using the split-token
 * (selector : validator) scheme with server-side storage.
 *
 * The plaintext token is returned exactly once, by issue(). Only the hash of
 * its validator is stored, so a token that the application does not show to
 * the user at issue time is not recoverable afterwards.
 *
 * Unlike "remember me" cookies, tokens are NOT rotated on use: an API token is
 * routinely presented by several requests at once, and rotating it would
 * invalidate the copies still in flight. Tokens end by explicit revocation or
 * by expiry instead.
 *
 * @package Aura.Auth
 *
 */
class TokenService
{
    /**
     *
     * Server-side token storage.
     *
     * @var TokenStorageInterface
     *
     */
    protected $storage;

    /**
     *
     * The split-token helper.
     *
     * @var SplitToken
     *
     */
    protected $token;

    /**
     *
     * The default token lifetime in seconds.
     *
     * @var int
     *
     */
    protected $ttl;

    /**
     *
     * Constructor.
     *
     * @param TokenStorageInterface $storage Server-side token storage.
     *
     * @param SplitToken $token The split-token helper.
     *
     * @param int $ttl The default token lifetime in seconds (default 30 days).
     *
     */
    public function __construct(
        TokenStorageInterface $storage,
        SplitToken $token,
        $ttl = 2592000
    ) {
        $this->storage = $storage;
        $this->token = $token;
        $this->ttl = $ttl;
    }

    /**
     *
     * Issues a new token and returns its plaintext value.
     *
     * The returned value is the ONLY chance to see the token: storage keeps
     * just the hash of the validator. Show it to the user now or it is lost.
     *
     * @param string $username The user the token authenticates as.
     *
     * @param array $userdata Arbitrary application data to carry with the
     * token. The library stores and returns this without interpreting it;
     * applications that restrict a token's reach typically keep scopes here.
     *
     * @param string|null $label A human-readable name, e.g. "CI deploy".
     *
     * @param int|null $ttl Optional lifetime override, in seconds.
     *
     * @return string The plaintext `selector:validator` token value.
     *
     */
    public function issue(
        $username,
        array $userdata = array(),
        $label = null,
        $ttl = null
    ): string {
        $now = time();
        $selector = $this->token->newSelector();
        $validator = $this->token->newValidator();

        $this->storage->create(
            $selector,
            $this->token->hash($validator),
            $username,
            $userdata,
            $now + (($ttl === null) ? $this->ttl : $ttl),
            $now,
            $label
        );

        return $this->token->makeValue($selector, $validator);
    }

    /**
     *
     * Verifies a plaintext token and returns its stored row.
     *
     * Returns null for a malformed value, an unknown selector, or a validator
     * that does not match. Unknown and mismatched in particular are made
     * indistinguishable on purpose: telling them apart would reveal whether a
     * given selector exists, and selectors travel in the clear as half of the
     * token value.
     *
     * An expired token is the one failure reported distinctly, by throwing
     * {@see \Aura\Auth\Exception\TokenExpired}. That is safe because the
     * validator is matched first, so only a caller already holding the genuine
     * token can reach it — and it lets an API answer "your token expired,
     * please issue a new one" instead of a flat refusal.
     *
     * A failed verification deliberately does NOT delete the stored token. The
     * selector travels in the clear as half of the token value, so deleting on
     * a validator mismatch would let anyone who has merely seen a selector
     * revoke somebody else's token by presenting it with a wrong validator.
     * (This is the opposite of the "remember me" flow, where a mismatch is
     * treated as evidence of a stolen cookie and the token is discarded.)
     * Expired rows are cleared by deleteExpired() instead.
     *
     * @param string $value The plaintext `selector:validator` token value.
     *
     * @return array|null The stored token row, or null if verification failed.
     *
     * @throws TokenExpired when the token is genuine but past its expiry.
     *
     */
    public function verify(#[\SensitiveParameter] $value): ?array
    {
        // The validator is matched BEFORE the expiry is looked at. Reaching
        // the expiry check therefore proves the caller holds the real token,
        // which is what makes it safe to report expiry distinctly. Checking
        // expiry first would let an unknown-vs-existing selector be told apart.
        $row = $this->findGenuine($value);
        if (! $row) {
            return null;
        }

        if ($row['expires'] < time()) {
            throw TokenExpired::at((int) $row['expires']);
        }

        // no-op unless the storage was built with last-use tracking on
        $this->storage->touch($row['selector'], time());

        return $row;
    }

    /**
     *
     * Finds the stored row for a token whose validator matches.
     *
     * This is the single place the secret is compared, shared by verify() and
     * revoke() so that the two cannot drift apart on it. It deliberately says
     * nothing about expiry: callers decide what an expired-but-genuine token
     * means to them.
     *
     * @param string $value The plaintext `selector:validator` token value.
     *
     * @return array|null The stored row, or null if the value is malformed,
     * the selector is unknown, or the validator does not match. These are not
     * distinguished, so that a caller cannot learn whether a selector exists.
     *
     */
    protected function findGenuine(#[\SensitiveParameter] $value): ?array
    {
        $parsed = $this->token->parseValue($value);
        if (! $parsed) {
            return null;
        }

        $row = $this->storage->findBySelector($parsed['selector']);
        if (! $row) {
            return null;
        }

        if (! $this->token->verify($row['hashed_validator'], $parsed['validator'])) {
            return null;
        }

        return $row;
    }

    /**
     *
     * Revokes a token, given its plaintext value.
     *
     * The token is verified before being deleted, so that holding a selector
     * alone is not enough to revoke somebody else's token. An already-expired
     * token can still be revoked, since its validator matched.
     *
     * @param string $value The plaintext `selector:validator` token value.
     *
     * @return bool True if a token was revoked.
     *
     */
    public function revoke(#[\SensitiveParameter] $value): bool
    {
        // Expiry is not consulted: an expired token is still genuine, and
        // expiry should not be the reason it cannot be cleaned up. Nor is the
        // row touched — it is about to be deleted.
        $row = $this->findGenuine($value);
        if (! $row) {
            return false;
        }

        $this->storage->deleteBySelector($row['selector']);
        return true;
    }

    /**
     *
     * Revokes a token by its selector, without seeing the validator.
     *
     * This is for application-side management — a "revoke" button next to a
     * token in a list, where the user has already been authenticated by other
     * means. Do NOT call it with a selector taken straight from an incoming
     * request; use revoke() for that.
     *
     * @param string $selector The public lookup key.
     *
     * @return void
     *
     */
    public function revokeBySelector($selector): void
    {
        $this->storage->deleteBySelector($selector);
    }

    /**
     *
     * Revokes every token belonging to a user ("revoke all my tokens").
     *
     * @param string $username The user name.
     *
     * @return void
     *
     */
    public function revokeAll($username): void
    {
        $this->storage->deleteByUsername($username);
    }

    /**
     *
     * Deletes expired tokens (housekeeping); call this periodically.
     *
     * @return void
     *
     */
    public function deleteExpired(): void
    {
        $this->storage->deleteExpired();
    }
}

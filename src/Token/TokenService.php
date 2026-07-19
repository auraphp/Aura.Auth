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
     * Returns null for every kind of failure — malformed value, unknown
     * selector, expired token, or a validator that does not match — so that a
     * caller cannot distinguish them.
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
     */
    public function verify($value): ?array
    {
        $parsed = $this->token->parseValue($value);
        if (! $parsed) {
            return null;
        }

        $row = $this->storage->findBySelector($parsed['selector']);
        if (! $row) {
            return null;
        }

        if ($row['expires'] < time()) {
            return null;
        }

        if (! $this->token->verify($row['hashed_validator'], $parsed['validator'])) {
            return null;
        }

        // no-op unless the storage was built with last-use tracking on
        $this->storage->touch($parsed['selector'], time());

        return $row;
    }

    /**
     *
     * Revokes a token, given its plaintext value.
     *
     * The token is verified before being deleted, so that holding a selector
     * alone is not enough to revoke somebody else's token.
     *
     * @param string $value The plaintext `selector:validator` token value.
     *
     * @return bool True if a token was revoked.
     *
     */
    public function revoke($value): bool
    {
        if (! $this->verify($value)) {
            return false;
        }

        $parsed = $this->token->parseValue($value);
        $this->storage->deleteBySelector($parsed['selector']);
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

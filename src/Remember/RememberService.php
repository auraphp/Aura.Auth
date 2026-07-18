<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Remember;

use Aura\Auth\Auth;
use Aura\Auth\Status;
use Aura\Session_Interface\SessionInterface;

/**
 *
 * "Remember me" handler using the split-token (selector : validator) scheme
 * with server-side storage and per-use token rotation.
 *
 * @package Aura.Auth
 *
 */
class RememberService
{
    /**
     *
     * Server-side token storage.
     *
     * @var RememberStorageInterface
     *
     */
    protected $storage;

    /**
     *
     * A session manager.
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     * The split-token helper.
     *
     * @var Token
     *
     */
    protected $token;

    /**
     *
     * The cookie reader/writer.
     *
     * @var Cookie
     *
     */
    protected $cookie;

    /**
     *
     * The remember-me cookie name.
     *
     * @var string
     *
     */
    protected $name;

    /**
     *
     * The token lifetime in seconds.
     *
     * @var int
     *
     */
    protected $ttl;

    /**
     *
     * Constructor.
     *
     * @param RememberStorageInterface $storage Server-side token storage.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param Token $token The split-token helper.
     *
     * @param Cookie $cookie The cookie reader/writer.
     *
     * @param string $name The remember-me cookie name.
     *
     * @param int $ttl The token lifetime in seconds (default 30 days).
     *
     */
    public function __construct(
        RememberStorageInterface $storage,
        SessionInterface $session,
        Token $token,
        Cookie $cookie,
        $name = 'remember',
        $ttl = 2592000
    ) {
        $this->storage = $storage;
        $this->session = $session;
        $this->token = $token;
        $this->cookie = $cookie;
        $this->name = $name;
        $this->ttl = $ttl;
    }

    /**
     *
     * Issues a new remember-me token for the current user and sets the cookie.
     *
     * @param Auth $auth The authentication tracker (source of the user name and
     * user data to remember).
     *
     * @param int $ttl Optional token lifetime override, in seconds.
     *
     * @return bool True if a token was issued.
     *
     */
    public function remember(Auth $auth, $ttl = null): bool
    {
        $username = $auth->getUserName();
        if ($username === null || $username === '') {
            return false;
        }

        $expires = $this->expires($ttl);
        $selector = $this->token->newSelector();
        $validator = $this->token->newValidator();

        $this->storage->create(
            $selector,
            $this->token->hash($validator),
            $username,
            $auth->getUserData(),
            $expires
        );

        $this->cookie->set(
            $this->name,
            $this->token->makeCookieValue($selector, $validator),
            $expires
        );

        return true;
    }

    /**
     *
     * Attempts to re-establish authentication from a remember-me cookie,
     * rotating the token on success and setting the status to REMEMBERED.
     *
     * Only acts when the user is currently anonymous.
     *
     * @param Auth $auth The authentication tracker.
     *
     * @param int $ttl Optional token lifetime override for the rotated token.
     *
     * @return bool True if the user was remembered.
     *
     */
    public function resume(Auth $auth, $ttl = null): bool
    {
        if (! $auth->isAnon()) {
            return false;
        }

        // If the client sent no remember-me cookie, do nothing. In particular
        // do NOT emit a deletion cookie: resume() runs on every unauthenticated
        // request, and a Set-Cookie header there would needlessly pollute
        // responses and defeat HTTP caching for anonymous users.
        $value = $this->cookie->get($this->name);
        if ($value === null || $value === '') {
            return false;
        }

        // A cookie was present but is malformed; clear it.
        $parsed = $this->token->parseCookieValue($value);
        if (! $parsed) {
            $this->cookie->delete($this->name);
            return false;
        }

        $row = $this->storage->findBySelector($parsed['selector']);

        // unknown selector, expired, or validator mismatch (possible theft):
        // discard the token in all cases.
        if (! $row
            || $row['expires'] < time()
            || ! $this->token->verify($row['hashed_validator'], $parsed['validator'])
        ) {
            if ($row) {
                $this->storage->deleteBySelector($parsed['selector']);
            }
            $this->cookie->delete($this->name);
            return false;
        }

        $started = $this->session->resume() || $this->session->start();
        if (! $started) {
            return false;
        }
        $this->session->regenerateId();

        // rotate the validator so a stolen cookie is single-use
        $expires = $this->expires($ttl);
        $validator = $this->token->newValidator();
        $this->storage->update(
            $parsed['selector'],
            $this->token->hash($validator),
            $expires
        );
        $this->cookie->set(
            $this->name,
            $this->token->makeCookieValue($parsed['selector'], $validator),
            $expires
        );

        $auth->set(
            Status::REMEMBERED,
            time(),
            time(),
            $row['username'],
            $row['userdata']
        );

        return true;
    }

    /**
     *
     * Discards the remember-me token: deletes it from storage and clears the
     * cookie. Call this on logout.
     *
     * @param Auth $auth The authentication tracker (unused; accepted for a
     * consistent service signature).
     *
     * @return void
     *
     */
    public function forget(?Auth $auth = null): void
    {
        $parsed = $this->token->parseCookieValue(
            $this->cookie->get($this->name)
        );
        if ($parsed) {
            $this->storage->deleteBySelector($parsed['selector']);
        }
        $this->cookie->delete($this->name);
    }

    /**
     *
     * Computes an expiry timestamp from a ttl (or the default ttl).
     *
     * @param int $ttl Optional ttl override in seconds.
     *
     * @return int
     *
     */
    protected function expires($ttl): int
    {
        $ttl = ($ttl === null) ? $this->ttl : $ttl;
        return time() + $ttl;
    }
}

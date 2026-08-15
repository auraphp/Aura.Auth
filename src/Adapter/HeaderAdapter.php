<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\Auth;
use Aura\Auth\Exception\TokenExpired;
use Aura\Auth\Exception\TokenInvalid;
use Aura\Auth\Exception\TokenMissing;
use Aura\Auth\Status;
use Aura\Auth\Token\TokenService;

/**
 *
 * Authenticates a request from an opaque API token carried in an HTTP header.
 *
 * This is the stateless counterpart to the session-backed adapters: nothing is
 * written to $_SESSION and no cookie is set or read. Each request stands alone,
 * carrying its own credential, which is what makes it a fit for REST APIs —
 * there is no session to lock, and no cookie for a cross-site request to abuse.
 *
 * Pair it with {@see \Aura\Auth\Service\ApiResumeService} and an
 * {@see \Aura\Auth\Session\ArraySegment}-backed {@see Auth}.
 *
 * Tokens are issued by {@see TokenService::issue()}, not by this adapter:
 * exchanging a password for a token is an ordinary login, so it belongs to
 * whichever adapter already verifies the user's credentials.
 *
 * @package Aura.Auth
 *
 */
class HeaderAdapter extends AbstractAdapter
{
    /**
     *
     * The token issuing and verification service.
     *
     * @var TokenService
     *
     */
    protected $token_service;

    /**
     *
     * A copy of $_SERVER (or an equivalent map of request headers).
     *
     * @var array
     *
     */
    protected $server;

    /**
     *
     * The $_SERVER key to read the token from.
     *
     * @var string
     *
     */
    protected $header;

    /**
     *
     * A scheme prefix to strip from the header value, e.g. "Bearer ".
     *
     * @var string
     *
     */
    protected $prefix;

    /**
     *
     * Constructor.
     *
     * @param TokenService $token_service The token verification service.
     *
     * @param array $server A copy of $_SERVER.
     *
     * @param array $options Optional keys: `header`, the $_SERVER key holding
     * the token (default `HTTP_AUTHORIZATION`); and `prefix`, a scheme prefix
     * to strip from it (default `Bearer `). Pass an empty prefix to read the
     * header value verbatim, e.g. for a bare `X-Api-Token` header.
     *
     */
    public function __construct(
        TokenService $token_service,
        array $server,
        array $options = array()
    ) {
        $this->token_service = $token_service;
        $this->server = $server;
        $this->header = isset($options['header'])
            ? $options['header']
            : 'HTTP_AUTHORIZATION';
        $this->prefix = isset($options['prefix'])
            ? $options['prefix']
            : 'Bearer ';
    }

    /**
     *
     * Verifies the presented token and returns its login data.
     *
     * @param array $input Optional credential input; a `token` key overrides
     * the request header, which is useful when the token arrives by some other
     * route (a query parameter on a websocket handshake, say).
     *
     * @return array A `list($username, $userdata)` pair.
     *
     * @throws TokenMissing when no token was presented.
     *
     * @throws TokenInvalid when the token is malformed, unknown, or does not
     * match. These are deliberately not distinguished.
     *
     * @throws TokenExpired when the token is genuine but past its expiry.
     *
     */
    public function login(#[\SensitiveParameter] array $input): array
    {
        $value = isset($input['token']) ? $input['token'] : $this->getToken();

        if ($value === null || $value === '') {
            throw new TokenMissing;
        }

        // TokenExpired propagates: the caller has proven it holds the real
        // token, so it can be told to get a fresh one.
        $row = $this->token_service->verify($value);

        if (! $row) {
            throw new TokenInvalid;
        }

        return array($row['username'], $row['userdata']);
    }

    /**
     *
     * Authenticates the request from its token, if it carries a usable one.
     *
     * A missing or invalid token leaves the Auth object untouched — anonymous —
     * rather than throwing, so that this adapter can sit in a chain that also
     * serves unauthenticated requests. An expired token still throws, since
     * silently treating a request as anonymous when the client believes it is
     * authenticated is the more confusing outcome, and the client has proven it
     * holds the real token.
     *
     * @param Auth $auth The authentication object to populate.
     *
     * @return void
     *
     * @throws TokenExpired when the token is genuine but past its expiry.
     *
     */
    public function resume(Auth $auth): void
    {
        try {
            list($username, $userdata) = $this->login(array());
        } catch (TokenMissing $e) {
            return;
        } catch (TokenInvalid $e) {
            return;
        }

        $now = time();
        $auth->set(Status::VALID, $now, $now, $username, $userdata);
    }

    /**
     *
     * Revokes the token the request was authenticated with.
     *
     * There is no session to destroy, so "logging out" of a token-authenticated
     * request means revoking that token server-side; every other copy of it
     * stops working too.
     *
     * @param Auth $auth The authentication object being logged out.
     *
     * @param string $status The new authentication status.
     *
     * @return void
     *
     */
    public function logout(Auth $auth, $status = Status::ANON): void
    {
        $value = $this->getToken();
        if ($value !== null && $value !== '') {
            $this->token_service->revoke($value);
        }
    }

    /**
     *
     * Reads the token out of the request headers.
     *
     * @return string|null The token value, or null if absent or if the header
     * does not carry the expected scheme prefix.
     *
     */
    protected function getToken(): ?string
    {
        if (! isset($this->server[$this->header])) {
            return null;
        }

        $value = trim((string) $this->server[$this->header]);

        if ($this->prefix === '') {
            return ($value === '') ? null : $value;
        }

        // compare the scheme case-insensitively: RFC 7235 makes it a
        // case-insensitive token, and clients differ on "Bearer" vs "bearer"
        $len = strlen($this->prefix);
        if (strncasecmp($value, $this->prefix, $len) !== 0) {
            return null;
        }

        $value = trim(substr($value, $len));
        return ($value === '') ? null : $value;
    }
}

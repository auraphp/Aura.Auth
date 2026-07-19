<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\OAuth;

/**
 *
 * The result of building an authorization request: the URL to redirect the user
 * to, the anti-CSRF `state` to persist and later validate, and (when the
 * provider supports PKCE) the `code_verifier` to persist for the token exchange.
 *
 * @package Aura.Auth
 *
 */
class AuthorizationRequest
{
    /**
     *
     * The authorization URL to redirect the user to.
     *
     * @var string
     *
     */
    protected $url;

    /**
     *
     * The anti-CSRF state value.
     *
     * @var string
     *
     */
    protected $state;

    /**
     *
     * The PKCE code verifier, or null if PKCE is not in use.
     *
     * @var string|null
     *
     */
    protected $code_verifier;

    /**
     *
     * Constructor.
     *
     * @param string $url The authorization URL.
     *
     * @param string $state The anti-CSRF state value.
     *
     * @param string|null $code_verifier The PKCE code verifier, if any.
     *
     */
    public function __construct(string $url, string $state, ?string $code_verifier = null)
    {
        $this->url = $url;
        $this->state = $state;
        $this->code_verifier = $code_verifier;
    }

    /**
     *
     * Returns the authorization URL.
     *
     * @return string
     *
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     *
     * Returns the anti-CSRF state value.
     *
     * @return string
     *
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     *
     * Returns the PKCE code verifier, or null if PKCE is not in use.
     *
     * @return string|null
     *
     */
    public function getCodeVerifier(): ?string
    {
        return $this->code_verifier;
    }
}

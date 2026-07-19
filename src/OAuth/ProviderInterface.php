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
 * A minimal seam over an OAuth 2.0 client, so Aura.Auth does not hard-depend on
 * any particular client library. A thin `LeagueProvider` wrapping
 * `league/oauth2-client` is provided; you may implement this interface over any
 * other client.
 *
 * @package Aura.Auth
 *
 */
interface ProviderInterface
{
    /**
     *
     * Builds an authorization request: the URL to redirect to, plus the
     * anti-CSRF `state` (and PKCE `code_verifier` when supported) to persist and
     * validate at the callback.
     *
     * @param array $options Extra options for the underlying client (e.g.
     * `scope`).
     *
     * @return AuthorizationRequest
     *
     */
    public function getAuthorizationRequest(array $options = []): AuthorizationRequest;

    /**
     *
     * Exchanges an authorization code for an access token.
     *
     * @param string $code The authorization code from the validated callback.
     *
     * @param string|null $code_verifier The PKCE code verifier persisted at the
     * redirect step, if any.
     *
     * @return mixed The access token (as returned by the underlying client).
     *
     */
    public function getAccessToken(string $code, ?string $code_verifier = null);

    /**
     *
     * Fetches the resource owner (the authenticated user) for an access token.
     *
     * @param mixed $token The access token from getAccessToken().
     *
     * @return array The resource owner's data as an associative array.
     *
     */
    public function getResourceOwner($token): array;
}

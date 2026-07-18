<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\OAuth;

use Aura\Auth\Exception;
use Aura\Session_Interface\SegmentInterface;

/**
 *
 * Drives the two halves of the OAuth 2.0 authorization-code flow securely:
 *
 * - `getRedirectUrl()` generates the anti-CSRF `state` (and a PKCE
 *   `code_verifier` when the provider supports it), persists them in the session
 *   segment, and returns the URL to redirect the user to.
 *
 * - `handleCallback()` handles a provider error explicitly, validates the
 *   returned `state` against the persisted one in constant time, and returns
 *   only the **validated** parameters (`code`, and `code_verifier` for PKCE) to
 *   hand to `LoginService::login()`. Never pass a raw `$_GET` to `login()`.
 *
 * The segment MUST be backed by a real session so the values survive the
 * redirect round-trip.
 *
 * @package Aura.Auth
 *
 */
class AuthorizationCodeFlow
{
    /**
     *
     * The OAuth 2.0 provider seam.
     *
     * @var ProviderInterface
     *
     */
    protected $provider;

    /**
     *
     * The session segment used to persist state and the PKCE verifier.
     *
     * @var SegmentInterface
     *
     */
    protected $segment;

    /**
     *
     * The segment key for the anti-CSRF state.
     *
     * @var string
     *
     */
    protected $state_key;

    /**
     *
     * The segment key for the PKCE code verifier.
     *
     * @var string
     *
     */
    protected $verifier_key;

    /**
     *
     * Constructor.
     *
     * @param ProviderInterface $provider The OAuth 2.0 provider seam.
     *
     * @param SegmentInterface $segment A session-backed segment for persisting
     * the state and PKCE verifier across the redirect.
     *
     * @param array $options Optional key overrides: `state_key`,
     * `verifier_key`.
     *
     */
    public function __construct(
        ProviderInterface $provider,
        SegmentInterface $segment,
        array $options = []
    ) {
        $this->provider = $provider;
        $this->segment = $segment;
        $this->state_key = $options['state_key'] ?? 'oauth2_state';
        $this->verifier_key = $options['verifier_key'] ?? 'oauth2_code_verifier';
    }

    /**
     *
     * Builds the authorization URL, persisting the state and PKCE verifier.
     *
     * @param array $options Extra options for the underlying client (e.g.
     * `scope`).
     *
     * @return string The URL to redirect the user to.
     *
     */
    public function getRedirectUrl(array $options = []): string
    {
        $request = $this->provider->getAuthorizationRequest($options);
        $this->segment->set($this->state_key, $request->getState());
        $this->segment->set($this->verifier_key, $request->getCodeVerifier());
        return $request->getUrl();
    }

    /**
     *
     * Validates a provider callback and returns the parameters to pass to
     * `LoginService::login()`.
     *
     * @param array $query The callback query parameters (e.g. `$_GET`).
     *
     * @return array The validated input: `code`, and `code_verifier` when PKCE
     * is in use.
     *
     * @throws Exception\OAuth2CallbackError when the provider reported an error.
     *
     * @throws Exception\OAuth2StateMismatch when the state is missing or does
     * not match (possible CSRF).
     *
     * @throws Exception\AuthorizationCodeMissing when no code is present.
     *
     */
    public function handleCallback(array $query): array
    {
        // 1. Validate state (constant-time), consuming it so it cannot replay.
        $expected = $this->segment->get($this->state_key);
        $this->segment->set($this->state_key, null);

        $returned = isset($query['state']) && is_string($query['state']) ? $query['state'] : '';
        if (! is_string($expected) || $expected === '' || ! hash_equals($expected, $returned)) {
            $this->segment->set($this->verifier_key, null);
            throw new Exception\OAuth2StateMismatch();
        }

        // 2. Handle a provider-reported error.
        if (isset($query['error'])) {
            $this->segment->set($this->verifier_key, null);
            $description = isset($query['error_description']) && is_string($query['error_description'])
                ? $query['error_description']
                : (is_string($query['error']) ? $query['error'] : '');
            throw new Exception\OAuth2CallbackError($description);
        }

        // 3. Require a string code, and only now surface the validated parameters.
        if (empty($query['code']) || !is_string($query['code'])) {
            $this->segment->set($this->verifier_key, null);
            throw new Exception\AuthorizationCodeMissing();
        }

        $verifier = $this->segment->get($this->verifier_key);
        $this->segment->set($this->verifier_key, null);

        $input = ['code' => $query['code']];
        if (is_string($verifier) && $verifier !== '') {
            $input['code_verifier'] = $verifier;
        }

        return $input;
    }
}

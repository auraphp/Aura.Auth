<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;

/**
 *
 * Wraps a `league/oauth2-client` provider as an Aura.Auth
 * {@see ProviderInterface}.
 *
 * `league/oauth2-client` is not a hard dependency of this package; install it
 * (and a provider package such as `league/oauth2-google`) to use this wrapper.
 *
 * @package Aura.Auth
 *
 */
class LeagueProvider implements ProviderInterface
{
    /**
     *
     * The wrapped League provider.
     *
     * @var AbstractProvider
     *
     */
    protected $provider;

    /**
     *
     * Constructor.
     *
     * @param AbstractProvider $provider A configured League OAuth2 provider.
     *
     */
    public function __construct(AbstractProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function getAuthorizationRequest(array $options = []): AuthorizationRequest
    {
        // League generates and stores the state (and, when the provider enables
        // PKCE, the code challenge) as a side effect of building the URL.
        $url = $this->provider->getAuthorizationUrl($options);
        $state = $this->provider->getState();

        $verifier = null;
        if (method_exists($this->provider, 'getPkceCode')) {
            $pkce = $this->provider->getPkceCode();
            $verifier = ($pkce === null || $pkce === '') ? null : $pkce;
        }

        return new AuthorizationRequest($url, $state, $verifier);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function getAccessToken(string $code, ?string $code_verifier = null)
    {
        if ($code_verifier !== null && method_exists($this->provider, 'setPkceCode')) {
            $this->provider->setPkceCode($code_verifier);
        }

        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function getResourceOwner($token): array
    {
        return $this->provider->getResourceOwner($token)->toArray();
    }
}

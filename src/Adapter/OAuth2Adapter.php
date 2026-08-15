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
use Aura\Auth\OAuth\ProviderInterface;

/**
 *
 * Authenticate against an OAuth 2.0 provider using the authorization-code flow.
 *
 * This adapter expects to be handed the **validated** callback parameters (the
 * authorization `code`, and optionally the PKCE `code_verifier`) — typically
 * from `Aura\Auth\OAuth\AuthorizationCodeFlow::handleCallback()`, which performs
 * the anti-CSRF `state` check and provider-error handling. Do not pass a raw
 * `$_GET` array directly.
 *
 * @package Aura.Auth
 *
 */
class OAuth2Adapter extends AbstractAdapter
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
     * The resource-owner field to use as the user name.
     *
     * @var string|null
     *
     */
    protected $username_field;

    /**
     *
     * A callback mapping the resource owner and token to a
     * `[$username, $userdata]` pair.
     *
     * @var callable|null
     *
     */
    protected $map;

    /**
     *
     * Constructor.
     *
     * @param ProviderInterface $provider The OAuth 2.0 provider seam.
     *
     * @param array $options Mapping options: `username_field` (a resource-owner
     * field name to use as the user name, with the full resource owner retained
     * as user data), and/or `map` (a callable `fn($resource_owner, $token):
     * array` returning `[$username, $userdata]` for full control).
     *
     */
    public function __construct(ProviderInterface $provider, array $options = [])
    {
        $this->provider = $provider;
        $this->username_field = $options['username_field'] ?? null;
        $this->map = $options['map'] ?? null;
    }

    /**
     *
     * Exchanges the authorization code for a token, fetches the resource owner,
     * and maps it to a user name and user data.
     *
     * @param array $input The validated callback parameters; must contain
     * `code`, and may contain `code_verifier` for PKCE.
     *
     * @return array A `[$username, $userdata]` pair.
     *
     * @throws Exception\AuthorizationCodeMissing when `code` is absent.
     *
     * @throws Exception\OAuth2MappingNotConfigured when neither `map` nor
     * `username_field` was configured.
     *
     */
    public function login(#[\SensitiveParameter] array $input): array
    {
        if (empty($input['code'])) {
            throw new Exception\AuthorizationCodeMissing();
        }

        $token = $this->provider->getAccessToken(
            $input['code'],
            $input['code_verifier'] ?? null
        );

        $owner = $this->provider->getResourceOwner($token);

        return $this->mapOwner($owner, $token);
    }

    /**
     *
     * Maps a resource owner and token to a `[$username, $userdata]` pair.
     *
     * @param array $owner The resource-owner data.
     *
     * @param mixed $token The access token.
     *
     * @return array
     *
     * @throws Exception\OAuth2MappingNotConfigured
     *
     */
    protected function mapOwner(array $owner, #[\SensitiveParameter] $token): array
    {
        if ($this->map !== null) {
            return ($this->map)($owner, $token);
        }

        if ($this->username_field !== null) {
            $username = $owner[$this->username_field] ?? null;
            return [$username, $owner];
        }

        throw new Exception\OAuth2MappingNotConfigured();
    }
}

<?php
namespace Aura\Auth\OAuth;

/**
 * A controllable ProviderInterface double for tests.
 */
class FakeProvider implements ProviderInterface
{
    public $url = 'https://provider.example.com/authorize?client_id=x';
    public $state = 'STATE-123';
    public $code_verifier = null;
    public $resource_owner = array();

    public $last_code;
    public $last_code_verifier;

    public function getAuthorizationRequest(array $options = []): AuthorizationRequest
    {
        return new AuthorizationRequest($this->url, $this->state, $this->code_verifier);
    }

    public function getAccessToken(string $code, ?string $code_verifier = null)
    {
        $this->last_code = $code;
        $this->last_code_verifier = $code_verifier;
        return 'TOKEN-for-' . $code;
    }

    public function getResourceOwner($token): array
    {
        return $this->resource_owner;
    }
}

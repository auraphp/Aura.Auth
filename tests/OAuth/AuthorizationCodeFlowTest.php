<?php
namespace Aura\Auth\OAuth;

use Aura\Auth\Session\FakeSegment;

class AuthorizationCodeFlowTest extends \PHPUnit\Framework\TestCase
{
    protected $provider;

    protected $segment;

    protected $flow;

    protected function setUp() : void
    {
        $this->provider = new FakeProvider;
        $this->segment = new FakeSegment;
        $this->flow = new AuthorizationCodeFlow($this->provider, $this->segment);
    }

    public function testGetRedirectUrlPersistsState()
    {
        $this->provider->state = 'ST-abc';
        $this->provider->code_verifier = 'VER-xyz';

        $url = $this->flow->getRedirectUrl();

        $this->assertSame($this->provider->url, $url);
        $this->assertSame('ST-abc', $this->segment->get('oauth2_state'));
        $this->assertSame('VER-xyz', $this->segment->get('oauth2_code_verifier'));
    }

    public function testHandleCallbackSuccess()
    {
        $this->provider->state = 'ST-abc';
        $this->flow->getRedirectUrl();

        $input = $this->flow->handleCallback(array(
            'state' => 'ST-abc',
            'code' => 'CODE-1',
        ));

        $this->assertSame(array('code' => 'CODE-1'), $input);
        // state is consumed so it cannot be replayed
        $this->assertNull($this->segment->get('oauth2_state'));
    }

    public function testHandleCallbackReturnsPkceVerifier()
    {
        $this->provider->state = 'ST-abc';
        $this->provider->code_verifier = 'VER-xyz';
        $this->flow->getRedirectUrl();

        $input = $this->flow->handleCallback(array(
            'state' => 'ST-abc',
            'code' => 'CODE-1',
        ));

        $this->assertSame('CODE-1', $input['code']);
        $this->assertSame('VER-xyz', $input['code_verifier']);
        $this->assertNull($this->segment->get('oauth2_code_verifier'));
    }

    public function testHandleCallbackProviderErrorThrows()
    {
        $this->provider->state = 'ST-abc';
        $this->flow->getRedirectUrl();

        $this->expectException('Aura\Auth\Exception\OAuth2CallbackError');
        $this->flow->handleCallback(array('state' => 'ST-abc', 'error' => 'access_denied'));
    }

    public function testHandleCallbackStateMismatchThrows()
    {
        $this->provider->state = 'ST-abc';
        $this->flow->getRedirectUrl();

        $this->expectException('Aura\Auth\Exception\OAuth2StateMismatch');
        $this->flow->handleCallback(array('state' => 'WRONG', 'code' => 'CODE-1'));
    }

    public function testHandleCallbackMissingStateThrows()
    {
        // no getRedirectUrl(), so nothing stored
        $this->expectException('Aura\Auth\Exception\OAuth2StateMismatch');
        $this->flow->handleCallback(array('code' => 'CODE-1'));
    }

    public function testHandleCallbackMissingCodeThrows()
    {
        $this->provider->state = 'ST-abc';
        $this->flow->getRedirectUrl();

        $this->expectException('Aura\Auth\Exception\AuthorizationCodeMissing');
        $this->flow->handleCallback(array('state' => 'ST-abc'));
    }
}

<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\OAuth\FakeProvider;

class OAuth2AdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $provider;

    protected function setUp() : void
    {
        $this->provider = new FakeProvider;
        $this->provider->resource_owner = array(
            'login' => 'boshag',
            'id' => 42,
            'email' => 'boshag@example.com',
        );
    }

    public function testLogin_usernameField()
    {
        $adapter = new OAuth2Adapter($this->provider, array('username_field' => 'login'));

        list($username, $userdata) = $adapter->login(array('code' => 'abc'));

        $this->assertSame('boshag', $username);
        $this->assertSame($this->provider->resource_owner, $userdata);
        $this->assertSame('abc', $this->provider->last_code);
    }

    public function testLogin_mapCallback()
    {
        $adapter = new OAuth2Adapter($this->provider, array(
            'map' => function ($owner, $token) {
                return array($owner['email'], array('id' => $owner['id'], 'token' => $token));
            },
        ));

        list($username, $userdata) = $adapter->login(array('code' => 'abc'));

        $this->assertSame('boshag@example.com', $username);
        $this->assertSame(42, $userdata['id']);
        $this->assertSame('TOKEN-for-abc', $userdata['token']);
    }

    public function testLogin_passesPkceVerifier()
    {
        $adapter = new OAuth2Adapter($this->provider, array('username_field' => 'login'));

        $adapter->login(array('code' => 'abc', 'code_verifier' => 'v-e-r-i-f-i-e-r'));

        $this->assertSame('v-e-r-i-f-i-e-r', $this->provider->last_code_verifier);
    }

    public function testLogin_missingCodeThrows()
    {
        $adapter = new OAuth2Adapter($this->provider, array('username_field' => 'login'));

        $this->expectException('Aura\Auth\Exception\AuthorizationCodeMissing');
        $adapter->login(array());
    }

    public function testLogin_noMappingThrows()
    {
        $adapter = new OAuth2Adapter($this->provider);

        $this->expectException('Aura\Auth\Exception\OAuth2MappingNotConfigured');
        $adapter->login(array('code' => 'abc'));
    }

    public function testLogoutAndResumeAreNoops()
    {
        $adapter = new OAuth2Adapter($this->provider);
        $auth = new \Aura\Auth\Auth(new \Aura\Auth\Session\FakeSegment);
        // inherited AbstractAdapter no-ops must not error
        $this->assertNull($adapter->logout($auth));
        $this->assertNull($adapter->resume($auth));
    }
}

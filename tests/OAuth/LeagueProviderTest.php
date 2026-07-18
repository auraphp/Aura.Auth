<?php
namespace Aura\Auth\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

class LeagueProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAuthorizationRequest()
    {
        $league = $this->createMock(AbstractProvider::class);
        $league->method('getAuthorizationUrl')->willReturn('https://p.example/auth');
        $league->method('getState')->willReturn('STATE-9');

        $provider = new LeagueProvider($league);
        $request = $provider->getAuthorizationRequest(array('scope' => array('email')));

        $this->assertSame('https://p.example/auth', $request->getUrl());
        $this->assertSame('STATE-9', $request->getState());
    }

    public function testGetAccessTokenPassesCode()
    {
        $league = $this->createMock(AbstractProvider::class);
        $league->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', array('code' => 'CODE-1'))
            ->willReturn(new AccessToken(array('access_token' => 'TOKEN-1')));

        $provider = new LeagueProvider($league);
        $this->assertInstanceOf(AccessToken::class, $provider->getAccessToken('CODE-1'));
    }

    public function testGetResourceOwnerReturnsArray()
    {
        $owner = $this->createMock(ResourceOwnerInterface::class);
        $owner->method('toArray')->willReturn(array('email' => 'a@b.com', 'id' => 7));

        $token = new AccessToken(array('access_token' => 'TOKEN-1'));

        $league = $this->createMock(AbstractProvider::class);
        $league->method('getResourceOwner')->with($token)->willReturn($owner);

        $provider = new LeagueProvider($league);
        $this->assertSame(
            array('email' => 'a@b.com', 'id' => 7),
            $provider->getResourceOwner($token)
        );
    }
}

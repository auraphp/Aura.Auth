<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Auth;
use Aura\Auth\Exception\TokenExpired;
use Aura\Auth\Exception\TokenInvalid;
use Aura\Auth\Exception\TokenMissing;
use Aura\Auth\Phpfunc;
use Aura\Auth\Session\ArraySegment;
use Aura\Auth\Status;
use Aura\Auth\Token\FakeTokenStorage;
use Aura\Auth\Token\SplitToken;
use Aura\Auth\Token\TokenService;

class HeaderAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $storage;

    protected $service;

    protected function setUp() : void
    {
        $this->storage = new FakeTokenStorage;
        $this->service = new TokenService(
            $this->storage,
            new SplitToken(new Phpfunc)
        );
    }

    protected function newAdapter(array $server, array $options = array())
    {
        return new HeaderAdapter($this->service, $server, $options);
    }

    protected function newAuth()
    {
        return new Auth(new ArraySegment);
    }

    public function testLoginFromBearerHeader()
    {
        $value = $this->service->issue('boshag', array('foo' => 'bar'));
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        list($username, $userdata) = $adapter->login(array());
        $this->assertSame('boshag', $username);
        $this->assertSame(array('foo' => 'bar'), $userdata);
    }

    public function testSchemePrefixIsCaseInsensitive()
    {
        $value = $this->service->issue('boshag');
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'bearer ' . $value)
        );

        list($username) = $adapter->login(array());
        $this->assertSame('boshag', $username);
    }

    public function testCustomHeaderAndEmptyPrefix()
    {
        $value = $this->service->issue('boshag');
        $adapter = $this->newAdapter(
            array('HTTP_X_API_TOKEN' => $value),
            array('header' => 'HTTP_X_API_TOKEN', 'prefix' => '')
        );

        list($username) = $adapter->login(array());
        $this->assertSame('boshag', $username);
    }

    public function testTokenInInputOverridesHeader()
    {
        $value = $this->service->issue('boshag');
        $adapter = $this->newAdapter(array());

        list($username) = $adapter->login(array('token' => $value));
        $this->assertSame('boshag', $username);
    }

    public function testLoginThrowsWhenHeaderAbsent()
    {
        $this->expectException(TokenMissing::CLASS);
        $this->newAdapter(array())->login(array());
    }

    public function testLoginThrowsWhenSchemeDoesNotMatch()
    {
        $value = $this->service->issue('boshag');
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Basic ' . $value)
        );

        $this->expectException(TokenMissing::CLASS);
        $adapter->login(array());
    }

    public function testLoginThrowsWhenSchemeHasNoValue()
    {
        $adapter = $this->newAdapter(array('HTTP_AUTHORIZATION' => 'Bearer '));

        $this->expectException(TokenMissing::CLASS);
        $adapter->login(array());
    }

    public function testLoginThrowsOnUnknownToken()
    {
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer nosuch:validator')
        );

        $this->expectException(TokenInvalid::CLASS);
        $adapter->login(array());
    }

    public function testLoginThrowsOnMalformedToken()
    {
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer nocolon')
        );

        $this->expectException(TokenInvalid::CLASS);
        $adapter->login(array());
    }

    public function testLoginThrowsOnExpiredToken()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);
        $this->storage->rows[$parsed['selector']]['expires'] = time() - 1;

        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $this->expectException(TokenExpired::CLASS);
        $adapter->login(array());
    }

    public function testResumeAuthenticatesWithoutASession()
    {
        $this->assertFalse(isset($_SESSION));

        $value = $this->service->issue('boshag', array('foo' => 'bar'));
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $auth = $this->newAuth();
        $adapter->resume($auth);

        $this->assertTrue($auth->isValid());
        $this->assertSame(Status::VALID, $auth->getStatus());
        $this->assertSame('boshag', $auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $auth->getUserData());
        $this->assertFalse(isset($_SESSION));
    }

    public function testResumeLeavesAuthAnonymousWhenNoToken()
    {
        $auth = $this->newAuth();
        $this->newAdapter(array())->resume($auth);

        $this->assertTrue($auth->isAnon());
    }

    public function testResumeLeavesAuthAnonymousWhenTokenInvalid()
    {
        $auth = $this->newAuth();
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer nosuch:validator')
        );
        $adapter->resume($auth);

        $this->assertTrue($auth->isAnon());
    }

    public function testResumeThrowsOnExpiredToken()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);
        $this->storage->rows[$parsed['selector']]['expires'] = time() - 1;

        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $this->expectException(TokenExpired::CLASS);
        $adapter->resume($this->newAuth());
    }

    public function testLogoutRevokesTheToken()
    {
        $value = $this->service->issue('boshag');
        $adapter = $this->newAdapter(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $auth = $this->newAuth();
        $adapter->resume($auth);
        $adapter->logout($auth);

        $this->assertNull($this->service->verify($value));
    }

    public function testLogoutWithoutATokenDoesNothing()
    {
        $adapter = $this->newAdapter(array());
        $adapter->logout($this->newAuth());

        $this->assertSame(array(), $this->storage->rows);
    }
}

<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\HeaderAdapter;
use Aura\Auth\Auth;
use Aura\Auth\Exception\TokenExpired;
use Aura\Auth\Phpfunc;
use Aura\Auth\Session\ArraySegment;
use Aura\Auth\Token\FakeTokenStorage;
use Aura\Auth\Token\SplitToken;
use Aura\Auth\Token\TokenService;

class ApiResumeServiceTest extends \PHPUnit\Framework\TestCase
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

    protected function newApiResume(array $server)
    {
        return new ApiResumeService(
            new HeaderAdapter($this->service, $server)
        );
    }

    public function testResumeAuthenticatesFromHeader()
    {
        $value = $this->service->issue('boshag', array('foo' => 'bar'));
        $api = $this->newApiResume(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $auth = new Auth(new ArraySegment);
        $this->assertTrue($api->resume($auth));
        $this->assertSame('boshag', $auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $auth->getUserData());
    }

    public function testResumeTouchesNoSession()
    {
        $value = $this->service->issue('boshag');
        $api = $this->newApiResume(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $this->assertFalse(isset($_SESSION));
        $api->resume(new Auth(new ArraySegment));
        $this->assertFalse(isset($_SESSION));
    }

    public function testResumeReturnsFalseWithoutAToken()
    {
        $auth = new Auth(new ArraySegment);

        $this->assertFalse($this->newApiResume(array())->resume($auth));
        $this->assertTrue($auth->isAnon());
    }

    public function testResumeReturnsFalseOnInvalidToken()
    {
        $api = $this->newApiResume(
            array('HTTP_AUTHORIZATION' => 'Bearer nosuch:validator')
        );

        $auth = new Auth(new ArraySegment);
        $this->assertFalse($api->resume($auth));
        $this->assertTrue($auth->isAnon());
    }

    public function testResumeThrowsOnExpiredToken()
    {
        $value = $this->service->issue('boshag');
        $parsed = (new SplitToken(new Phpfunc))->parseValue($value);
        $this->storage->rows[$parsed['selector']]['expires'] = time() - 1;

        $api = $this->newApiResume(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        );

        $this->expectException(TokenExpired::CLASS);
        $api->resume(new Auth(new ArraySegment));
    }

    public function testEachRequestReauthenticatesIndependently()
    {
        // the stateless property: nothing carries over between requests, so a
        // second "request" with no header is anonymous even though the first
        // one authenticated
        $value = $this->service->issue('boshag');

        $first = new Auth(new ArraySegment);
        $this->newApiResume(
            array('HTTP_AUTHORIZATION' => 'Bearer ' . $value)
        )->resume($first);
        $this->assertTrue($first->isValid());

        $second = new Auth(new ArraySegment);
        $this->newApiResume(array())->resume($second);
        $this->assertTrue($second->isAnon());
    }
}

<?php
namespace Aura\Auth\Remember;

use Aura\Auth\Auth;
use Aura\Auth\Status;
use Aura\Auth\FakePhpfunc;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;

class RememberServiceTest extends \PHPUnit\Framework\TestCase
{
    protected $storage;

    protected $session;

    protected $phpfunc;

    protected function setUp() : void
    {
        $this->storage = new FakeRememberStorage;
        $this->session = new FakeSession;
        $this->phpfunc = new FakePhpfunc;
    }

    protected function newService(array $cookie = array())
    {
        return new RememberService(
            $this->storage,
            $this->session,
            new Token($this->phpfunc),
            new Cookie($this->phpfunc, $cookie),
            'remember',
            2592000
        );
    }

    protected function newAuth($status = Status::ANON)
    {
        $auth = new Auth(new FakeSegment);
        if ($status !== Status::ANON) {
            $auth->set($status, time(), time(), 'boshag', array('foo' => 'bar'));
        }
        return $auth;
    }

    public function testRememberCreatesTokenAndCookie()
    {
        $auth = $this->newAuth(Status::VALID);
        $service = $this->newService();

        $this->assertTrue($service->remember($auth));

        // one row stored, hashed validator (never the raw one)
        $this->assertCount(1, $this->storage->rows);
        $row = current($this->storage->rows);
        $this->assertSame('boshag', $row['username']);
        $this->assertSame(array('foo' => 'bar'), $row['userdata']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $row['hashed_validator']);

        // cookie set with hardened params
        $this->assertArrayHasKey('remember', $this->phpfunc->cookies);
        $opts = $this->phpfunc->cookies['remember']['options'];
        $this->assertTrue($opts['httponly']);
        $this->assertTrue($opts['secure']);
        $this->assertSame('Lax', $opts['samesite']);
    }

    public function testRememberDoesNothingWithoutUsername()
    {
        $auth = $this->newAuth(Status::ANON);
        $service = $this->newService();

        $this->assertFalse($service->remember($auth));
        $this->assertCount(0, $this->storage->rows);
    }

    public function testResumeSuccessRotatesToken()
    {
        // issue a token
        $service = $this->newService();
        $service->remember($this->newAuth(Status::VALID));
        $cookie_value = $this->phpfunc->cookies['remember']['value'];
        $selector = current($this->storage->rows)['selector'];
        $original_hash = current($this->storage->rows)['hashed_validator'];

        // new request carrying the cookie
        $auth = $this->newAuth(Status::ANON);
        $resume = $this->newService(array('remember' => $cookie_value));
        $this->assertTrue($resume->resume($auth));

        // remembered, with the stored identity restored
        $this->assertTrue($auth->isRemembered());
        $this->assertSame(Status::REMEMBERED, $auth->getStatus());
        $this->assertSame('boshag', $auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $auth->getUserData());

        // validator rotated: same selector, different hash, new cookie
        $this->assertArrayHasKey($selector, $this->storage->rows);
        $this->assertNotSame($original_hash, $this->storage->rows[$selector]['hashed_validator']);
        $this->assertNotSame($cookie_value, $this->phpfunc->cookies['remember']['value']);
    }

    public function testResumeDoesNothingWhenNotAnonymous()
    {
        $service = $this->newService();
        $service->remember($this->newAuth(Status::VALID));
        $cookie_value = $this->phpfunc->cookies['remember']['value'];

        $auth = $this->newAuth(Status::VALID);
        $resume = $this->newService(array('remember' => $cookie_value));
        $this->assertFalse($resume->resume($auth));
    }

    public function testResumeWithNoCookie()
    {
        $auth = $this->newAuth(Status::ANON);
        $service = $this->newService();
        $this->assertFalse($service->resume($auth));
        $this->assertTrue($auth->isAnon());

        // no cookie was sent, so no Set-Cookie header must be emitted
        // (resume() runs on every anonymous request; a deletion cookie here
        // would pollute responses and break HTTP caching)
        $this->assertArrayNotHasKey('remember', $this->phpfunc->cookies);
    }

    public function testResumeWithMalformedCookieClearsIt()
    {
        $auth = $this->newAuth(Status::ANON);
        // a present but unparseable value (no selector:validator separator)
        $service = $this->newService(array('remember' => 'garbage'));
        $this->assertFalse($service->resume($auth));
        $this->assertTrue($auth->isAnon());

        // a malformed cookie *was* present, so it is cleared
        $this->assertArrayHasKey('remember', $this->phpfunc->cookies);
        $this->assertSame('', $this->phpfunc->cookies['remember']['value']);
        $this->assertSame(1, $this->phpfunc->cookies['remember']['options']['expires']);
    }

    public function testResumeWithTamperedValidatorDeletesToken()
    {
        $service = $this->newService();
        $service->remember($this->newAuth(Status::VALID));
        $selector = current($this->storage->rows)['selector'];

        // forge a cookie with the right selector but a wrong validator
        $forged = $selector . ':' . str_repeat('0', 64);

        $auth = $this->newAuth(Status::ANON);
        $resume = $this->newService(array('remember' => $forged));
        $this->assertFalse($resume->resume($auth));
        $this->assertTrue($auth->isAnon());

        // suspected theft: the token is destroyed
        $this->assertCount(0, $this->storage->rows);
    }

    public function testResumeWithExpiredTokenDeletesToken()
    {
        $service = $this->newService();
        $service->remember($this->newAuth(Status::VALID));
        $cookie_value = $this->phpfunc->cookies['remember']['value'];
        $selector = current($this->storage->rows)['selector'];

        // force the stored token into the past
        $this->storage->rows[$selector]['expires'] = time() - 1;

        $auth = $this->newAuth(Status::ANON);
        $resume = $this->newService(array('remember' => $cookie_value));
        $this->assertFalse($resume->resume($auth));
        $this->assertCount(0, $this->storage->rows);
    }

    public function testForgetDeletesTokenAndCookie()
    {
        $service = $this->newService();
        $service->remember($this->newAuth(Status::VALID));
        $cookie_value = $this->phpfunc->cookies['remember']['value'];
        $this->assertCount(1, $this->storage->rows);

        $forget = $this->newService(array('remember' => $cookie_value));
        $forget->forget();

        $this->assertCount(0, $this->storage->rows);
        // deletion cookie written with an expiry in the past
        $this->assertSame('', $this->phpfunc->cookies['remember']['value']);
        $this->assertSame(1, $this->phpfunc->cookies['remember']['options']['expires']);
    }
}

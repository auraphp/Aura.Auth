<?php
namespace Aura\Auth;

use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;

class UserTest extends \PHPUnit_Framework_TestCase
{
    protected $user;

    protected $session;

    protected $segment;

    protected $timer;

    protected function setUp()
    {
        $this->session = new FakeSession;
        $this->segment = new FakeSegment;
        $this->timer = new Timer(1440, 14400);
        $this->user = new User(
            $this->session,
            $this->segment,
            $this->timer
        );
    }

    public function testForceLoginAndLogout()
    {
        $this->assertAnon();

        $result = $this->user->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertSame(Status::VALID, $result);
        $this->assertValid();

        $result = $this->user->forceLogout();
        $this->assertSame(Status::ANON, $result);
        $this->assertAnon();
    }

    public function testForceLogin_cannotResumeOrStart()
    {
        $this->session->allow_resume = false;
        $this->session->allow_start = false;

        $this->assertAnon();

        $result = $this->user->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertFalse($result);
        $this->assertAnon();
    }

    public function testForceLogout_cannotDestroy()
    {
        $this->session->allow_destroy = false;

        $result = $this->user->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertSame(Status::VALID, $result);
        $this->assertValid();

        $result = $this->user->forceLogout();
        $this->assertFalse($result);
        $this->assertValid();
    }

    public function testResumeSession()
    {
        $this->assertAnon();

        $this->user->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive($this->user->getLastActive() - 100);
        $this->assertSame(time() - 100, $this->user->getLastActive());

        $this->assertTrue($this->user->resumeSession());
        $this->assertSame(time(), $this->user->getLastActive());
    }

    public function testResumeSession_noneToResume()
    {
        $this->session->allow_resume = false;

        $this->assertAnon();
        $this->assertFalse($this->user->resumeSession());
        $this->assertAnon();
    }

    public function testResumeSession_expired()
    {
        $this->assertAnon();

        $this->user->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->user->isValid());

        $this->user->setFirstActive(time() - 14441);
        $this->assertTrue($this->user->resumeSession());
        $this->assertTrue($this->user->isExpired());

    }

    public function testRefresh_idle()
    {
        $this->assertAnon();

        $this->user->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 1441);
        $this->assertTrue($this->user->resumeSession());
        $this->assertTrue($this->user->isIdle());
    }

    protected function assertAnon()
    {
        $this->assertTrue($this->user->isAnon());
        $this->assertFalse($this->user->isValid());
        $this->assertNull($this->user->getName());
        $this->assertSame(array(), $this->user->getData());
        $this->assertNull($this->user->getFirstActive());
        $this->assertNull($this->user->getLastActive());
    }

    protected function assertValid()
    {
        $now = time();
        $this->assertFalse($this->user->isAnon());
        $this->assertTrue($this->user->isValid());
        $this->assertSame('boshag', $this->user->getName());
        $this->assertSame(array('foo' => 'bar'), $this->user->getData());
        $this->assertSame($now, $this->user->getFirstActive());
        $this->assertSame($now, $this->user->getLastActive());
    }
}

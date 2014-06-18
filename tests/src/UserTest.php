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

    // public function testRefresh()
    // {
    //     $this->assertAnon();

    //     $this->user->forceLogin('boshag', array('foo' => 'bar'));
    //     $this->assertTrue($this->user->isValid());

    //     $this->segment->last_active -= 100;
    //     $this->assertSame(time() - 100, $this->user->getLastActive());

    //     $this->user->refresh();
    //     $this->assertSame(time(), $this->user->getLastActive());
    // }

    // public function testRefresh_idle()
    // {
    //     $this->assertAnon();

    //     $this->user->forceLogin('boshag', array('foo' => 'bar'));
    //     $this->assertTrue($this->user->isValid());

    //     $this->segment->last_active -= 1441;
    //     $this->user->refresh();
    //     $this->assertTrue($this->user->isIdle());
    // }

    // public function testRefresh_expired()
    // {
    //     $this->assertAnon();

    //     $this->user->forceLogin('boshag', array('foo' => 'bar'));
    //     $this->assertValid();

    //     $this->segment->first_active -= 14441;
    //     $this->user->refresh();
    //     $this->assertTrue($this->user->isExpired());
    // }

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

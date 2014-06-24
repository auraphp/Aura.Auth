<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\User;
use Aura\Auth\Status;

class LogoutServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected $segment;

    protected $timer;

    protected $user;

    protected $adapter;

    protected function setUp()
    {
        $this->segment = new FakeSegment;
        $this->user = new User($this->segment);

        $this->session = new FakeSession;
        $this->adapter = new FakeAdapter;
        $this->handler = new LogoutService(
            $this->user,
            $this->session,
            $this->adapter
        );
    }

    public function testLogout()
    {
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->handler->logout();
        $this->assertTrue($this->user->isAnon());
    }

    public function testForceLogout_cannotDestroy()
    {
        $this->session->allow_destroy = false;

        $result = $this->handler->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertSame(Status::VALID, $result);
        $this->assertTrue($this->user->isValid());

        $result = $this->handler->forceLogout();
        $this->assertFalse($result);
        $this->assertTrue($this->user->isValid());
    }
}

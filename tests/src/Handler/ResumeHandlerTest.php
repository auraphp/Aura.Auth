<?php
namespace Aura\Auth\Handler;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\User;

class ResumeHandlerTest extends \PHPUnit_Framework_TestCase
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
        $this->timer = new Timer(1440, 14400);
        $this->handler = new ResumeHandler(
            $this->user,
            $this->session,
            $this->adapter,
            $this->timer
        );
    }

    public function testResume()
    {
        $this->assertTrue($this->user->isAnon());
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 100);
        $this->handler->resume();
        $this->assertTrue($this->user->isValid());
        $this->assertSame(time(), $this->user->getLastActive());
    }

    public function testResume_cannotResume()
    {
        $this->session->allow_resume = false;
        $this->assertTrue($this->user->isAnon());
        $this->handler->resume();
        $this->assertTrue($this->user->isAnon());
    }

    public function testResume_logoutIdle()
    {
        $this->assertTrue($this->user->isAnon());
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 1441);

        $this->handler->resume();
        $this->assertTrue($this->user->isIdle());
        $this->assertNull($this->user->getName());
    }

    public function testResume_logoutExpired()
    {
        $this->assertTrue($this->user->isAnon());
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setFirstActive(time() - 14441);

        $this->handler->resume();
        $this->assertTrue($this->user->isExpired());
        $this->assertNull($this->user->getName());
    }
}

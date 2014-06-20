<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\HtpasswdVerifier;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\User;

abstract class AbstractAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected $segment;

    protected $timer;

    protected $user;

    protected $adapter;

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

    public function testResume()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 100);
        $this->adapter->resume($this->user);
        $this->assertTrue($this->user->isValid());
        $this->assertSame(time(), $this->user->getLastActive());
    }

    public function testResume_cannotResume()
    {
        $this->session->allow_resume = false;
        $this->assertTrue($this->user->isAnon());
        $this->adapter->resume($this->user);
        $this->assertTrue($this->user->isAnon());
    }

    public function testResume_logoutIdle()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 1441);

        $this->adapter->resume($this->user);
        $this->assertTrue($this->user->isIdle());
        $this->assertNull($this->user->getName());
    }

    public function testResume_logoutExpired()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setFirstActive(time() - 14441);

        $this->adapter->resume($this->user);
        $this->assertTrue($this->user->isExpired());
        $this->assertNull($this->user->getName());
    }
}

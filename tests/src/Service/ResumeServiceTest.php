<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\Auth;

class ResumeServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected $segment;

    protected $timer;

    protected $auth;

    protected $adapter;

    protected function setUp()
    {

        $this->segment = new FakeSegment;
        $this->auth = new Auth($this->segment);

        $this->session = new FakeSession;
        $this->adapter = new FakeAdapter;
        $this->timer = new Timer(1440, 14400);
        $this->handler = new ResumeService(
            $this->auth,
            $this->session,
            $this->adapter,
            $this->timer
        );
    }

    public function testResume()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->setLastActive(time() - 100);
        $this->handler->resume();
        $this->assertTrue($this->auth->isValid());
        $this->assertSame(time(), $this->auth->getLastActive());
    }

    public function testResume_cannotResume()
    {
        $this->session->allow_resume = false;
        $this->assertTrue($this->auth->isAnon());
        $this->handler->resume();
        $this->assertTrue($this->auth->isAnon());
    }

    public function testResume_logoutIdle()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->setLastActive(time() - 1441);

        $this->handler->resume();
        $this->assertTrue($this->auth->isIdle());
        $this->assertNull($this->auth->getName());
    }

    public function testResume_logoutExpired()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->handler->forceLogin('boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->setFirstActive(time() - 14441);

        $this->handler->resume();
        $this->assertTrue($this->auth->isExpired());
        $this->assertNull($this->auth->getName());
    }
}

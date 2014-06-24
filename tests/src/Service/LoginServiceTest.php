<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\User;

class LoginServiceTest extends \PHPUnit_Framework_TestCase
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
        $this->handler = new LoginService(
            $this->user,
            $this->session,
            $this->adapter
        );
    }

    public function testLogin()
    {
        $this->assertTrue($this->user->isAnon());
        $this->handler->login(array('username' => 'boshag'));
        $this->assertTrue($this->user->isValid());
        $this->assertSame('boshag', $this->user->getName());
    }

    public function testForceLogin_cannotResumeOrStart()
    {
        $this->session->allow_resume = false;
        $this->session->allow_start = false;

        $this->assertTrue($this->user->isAnon());

        $result = $this->handler->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertFalse($result);
        $this->assertTrue($this->user->isAnon());
    }
}

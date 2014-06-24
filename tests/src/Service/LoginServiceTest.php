<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\Auth;

class LoginServiceTest extends \PHPUnit_Framework_TestCase
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
        $this->handler = new LoginService(
            $this->auth,
            $this->session,
            $this->adapter
        );
    }

    public function testLogin()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->handler->login(array('username' => 'boshag'));
        $this->assertTrue($this->auth->isValid());
        $this->assertSame('boshag', $this->auth->getUserName());
    }

    public function testForceLogin_cannotResumeOrStart()
    {
        $this->session->allow_resume = false;
        $this->session->allow_start = false;

        $this->assertTrue($this->auth->isAnon());

        $result = $this->handler->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertFalse($result);
        $this->assertTrue($this->auth->isAnon());
    }
}

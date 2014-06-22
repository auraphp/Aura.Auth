<?php
namespace Aura\Auth\Handler;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\User;

class LoginHandlerTest extends \PHPUnit_Framework_TestCase
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
        $this->user = new User(
            $this->session,
            $this->segment
        );

        $this->adapter = new FakeAdapter;

        $this->handler = new LoginHandler(
            $this->user,
            $this->adapter
        );
    }

    public function testLogin()
    {
        $this->assertTrue($this->user->isAnon());
        $this->handler->__invoke(array('username' => 'boshag'));
        $this->assertTrue($this->user->isValid());
        $this->assertSame('boshag', $this->user->getName());
    }
}
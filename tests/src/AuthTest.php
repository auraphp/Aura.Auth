<?php
namespace Aura\Auth;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected $auth;

    protected $adapter;

    protected $session;

    protected $timer;

    protected function setUp()
    {
        $this->adapter = new FakeAdapter(array('pmjones' => '12345'));
        $this->session = new FakeSession;
        $this->timer = new Timer(1440, 14400);
        $this->auth = new Auth($this->adapter, $this->session, $this->timer);
    }

    public function testGetProperties()
    {
        $this->assertSame($this->adapter, $this->auth->getAdapter());
        $this->assertSame($this->session, $this->auth->getSession());
        $this->assertSame($this->timer, $this->auth->getTimer());
    }
}

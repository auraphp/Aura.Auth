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

    public function testInstance()
    {
        $this->assertInstanceOf('Aura\Auth\Auth', $this->auth);
    }
}

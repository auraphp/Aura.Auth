<?php
namespace Aura\Auth;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected function setUp()
    {
        $this->session = new Session('Aura\Auth\Auth');
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Aura\Auth\Session', $this->session);
    }
}

<?php
namespace Aura\Auth\Handler;

class LogoutHandlerTest extends AbstractHandlerTest
{
    public function setUp()
    {
        parent::setUp();
        $this->handler = new LogoutHandler($this->auth, $this->adapter);
    }

    public function testLogout()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->auth->forceLogin('boshag');
        $this->assertTrue($this->auth->isValid());

        $this->handler->logout();
        $this->assertTrue($this->auth->isAnon());
    }

    // public function testLogin_bad()
    // {
    //     $this->assertAnon();

    //     $this->auth->login(array(
    //         'username' => 'boshag',
    //         'password' => '------',
    //     ));

    //     $this->assertSame(Auth::ERROR, $this->auth->getStatus());
    //     $expect = 'Password mismatch.';
    //     $actual = $this->auth->getError();
    //     $this->assertSame($expect, $actual);
    // }

    // public function testLogout_bad()
    // {
    //     $this->assertAnon();

    //     $this->auth->forceLogin('boshag', array(
    //         'adapter' => 'fake',
    //         'logout_error' => true,
    //     ));
    //     $this->assertValid();

    //     $this->auth->logout();
    //     $this->assertSame(Auth::ERROR, $this->auth->getStatus());
    //     $expect = 'Triggered logout error.';
    //     $actual = $this->auth->getError();
    //     $this->assertSame($expect, $actual);
    // }
}

<?php
namespace Aura\Auth\Handler;

class LoginHandlerTest extends AbstractHandlerTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->handler = new LoginHandler($this->auth, $this->adapter);
    }

    public function testLogin()
    {
        $this->assertTrue($this->auth->isAnon());

        $this->assertTrue($this->handler->login(array(
            'username' => 'boshag',
            'password' => '123456',
        )));

        $this->assertTrue($this->auth->isValid());
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

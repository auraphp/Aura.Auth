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

    public function testLogout_error()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->auth->forceLogin('boshag', array(
            'logout_error' => true,
        ));
        $this->assertTrue($this->auth->isValid());

        $this->handler->logout();
        $this->assertTrue($this->auth->isValid());

        $this->assertSame(
            $this->adapter->getError(),
            $this->handler->getError()
        );
    }
}

<?php
namespace Aura\Auth;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected function setUp()
    {
        session_set_save_handler(new MockSessionHandler);
        $this->session = new Session('Aura\Auth\Auth');
    }

    public function teardown()
    {
        session_unset();
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function testStart()
    {
        $this->session->start();
        $this->assertSessionExists();
    }

    public function testMagic()
    {
        $this->session->start();
        $this->assertFalse(isset($this->session->foo));
        $this->session->foo = 'bar';
        $this->assertTrue(isset($this->session->foo));
        $this->assertSame('bar', $this->session->foo);
        $this->assertSame('bar', $_SESSION['Aura\Auth\Auth']['foo']);
        unset($this->session->foo);
        $this->assertFalse(isset($this->session->foo));
    }

    protected function assertSessionExists()
    {
        $this->assertTrue(session_id() !== '');
    }

    protected function assertNoSession()
    {
        $this->assertTrue(session_id() === '');
    }

    public function testDestroy()
    {
        $this->session->start();
        $this->assertSessionExists();

        $this->session->foo = 'bar';
        $this->session->baz = 'dib';

        $expect = array('Aura\Auth\Auth' => array('foo' => 'bar', 'baz' => 'dib'));
        $this->assertSame($expect, $_SESSION);

        $this->session->destroy();
        $this->assertNoSession();
    }

    public function testGetAndRegenerateId()
    {
        $this->session->start();
        $old_id = session_id();
        $this->session->regenerateId();
        $new_id = session_id();
        $this->assertTrue($old_id != $new_id);
    }

}

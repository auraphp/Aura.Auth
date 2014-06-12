<?php
namespace Aura\Auth\Session;

abstract class AbstractSessionTest extends \PHPUnit_Framework_TestCase
{
    protected $data;

    protected $session;

    protected $session_id = 1;

    public function testMagic()
    {
        $this->assertFalse(isset($this->session->foo));
        $this->assertNull($this->session->foo);
        $this->session->foo = 'bar';
        $this->assertTrue(isset($this->session->foo));
        $this->assertSame('bar', $this->session->foo);
        $this->assertSame('bar', $this->getData('foo'));
        unset($this->session->foo);
        $this->assertFalse(isset($this->session->foo));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateId_native()
    {
        session_start();
        $old_id = session_id();
        $this->session->regenerateId();
        $new_id = session_id();
        $this->assertFalse($old_id === $new_id);
    }

    public function testRegenerateId_callable()
    {
        $this->session = new SessionArray(
            $this->data,
            array($this, 'regenerateId')
        );

        $old_id = $this->session_id;
        $this->session->regenerateId();
        $new_id = $this->session_id;
        $this->assertFalse($old_id === $new_id);
    }

    public function regenerateId()
    {
        $this->session_id ++;
    }
}

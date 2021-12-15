<?php
namespace Aura\Auth\Session;

class NullSessionTest extends \PHPUnit\Framework\TestCase
{
    protected $session;

    protected function setUp() : void
    {
        $this->session = new NullSession;
    }

    public function testStart()
    {
        $this->assertTrue(session_id() === '');
        $this->assertTrue($this->session->start());
        $this->assertTrue(session_id() === '');
    }

    public function testResume()
    {
        $this->assertTrue(session_id() === '');
        $this->assertFalse($this->session->resume());
        $this->assertTrue(session_id() === '');
    }

    public function testRegenerateId()
    {
        $this->assertTrue(session_id() === '');
        $this->assertTrue($this->session->regenerateId());
        $this->assertTrue(session_id() === '');
    }
}

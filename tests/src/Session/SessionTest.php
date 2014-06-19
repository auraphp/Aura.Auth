<?php
namespace Aura\Auth\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->setSession();
    }

    protected function setSession(array $cookies = array())
    {
        $this->session = new Session($cookies);
    }

    public function testStart()
    {
        // no session yet
        $this->assertTrue(session_id() === '');

        // start once
        $this->session->start();
        $id = session_id();
        $this->assertTrue(session_id() !== '');
    }

    public function testResume()
    {
        // fake a previous session cookie
        $this->setSession(array(session_name() => true));

        // no session yet
        $this->assertTrue(session_id() === '');

        // resume the pre-existing session
        $this->assertTrue($this->session->resume());

        // now we have a session
        $this->assertTrue(session_id() !== '');

        // try again after the session is already started
        $this->assertTrue($this->session->resume());
    }

    public function testResume_nonePrevious()
    {
        // no previous session cookie
        $cookies = array();
        $this->session = new Session($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // no pre-existing session to resume
        $this->assertFalse($this->session->resume());

        // still no session
        $this->assertTrue(session_id() === '');
    }

    public function testRegenerateId()
    {
        $cookies = array();
        $this->session = new Session($cookies);

        $this->session->start();
        $old_id = session_id();
        $this->assertTrue(session_id() !== '');

        $this->session->regenerateId();
        $new_id = session_id();
        $this->assertTrue($old_id !== $new_id);
    }

    public function testDestroy()
    {
        $this->session->start();
        $this->assertTrue($this->session->destroy());
    }
}

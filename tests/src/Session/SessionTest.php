<?php
namespace Aura\Auth\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function testStart()
    {
        $cookies = array();
        $manager = new Session($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // start once
        $manager->start();
        $id = session_id();
        $this->assertTrue(session_id() !== '');
    }


    public function testResume()
    {
        // fake a previous session cookie
        $cookies = array(session_name() => true);
        $manager = new Session($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // resume the pre-existing session
        $this->assertTrue($manager->resume());

        // now we have a session
        $this->assertTrue(session_id() !== '');
    }

    public function testResume_nonePrevious()
    {
        // no previous session cookie
        $cookies = array();
        $manager = new Session($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // no pre-existing session to resume
        $this->assertFalse($manager->resume());

        // still no session
        $this->assertTrue(session_id() === '');
    }
    public function testRegenerateId()
    {
        $cookies = array();
        $manager = new Session($cookies);

        $manager->start();
        $old_id = session_id();
        $this->assertTrue(session_id() !== '');

        $manager->regenerateId();
        $new_id = session_id();
        $this->assertTrue($old_id !== $new_id);
    }
}

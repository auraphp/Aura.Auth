<?php
namespace Aura\Auth\Session;

class SessionManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $start = false;
    protected $resume = false;
    protected $regenerate_id = false;

    /**
     * @runInSeparateProcess
     */
    public function testStart()
    {
        $cookies = array();
        $manager = new SessionManager($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // start once
        $manager->start();
        $id = session_id();
        $this->assertTrue(session_id() !== '');

        // try to start again
        $manager->start();
        $this->assertTrue(session_id() === $id);
    }


    /**
     * @runInSeparateProcess
     */
    public function testResume()
    {
        // fake a previous session cookie
        $cookies = array(session_name() => true);
        $manager = new SessionManager($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // resume the pre-existing session
        $this->assertTrue($manager->resume());

        // now we have a session
        $this->assertTrue(session_id() !== '');
    }

    /**
     * @runInSeparateProcess
     */
    public function testResume_nonePrevious()
    {
        // no previous session cookie
        $cookies = array();
        $manager = new SessionManager($cookies);

        // no session yet
        $this->assertTrue(session_id() === '');

        // no pre-existing session to resume
        $this->assertFalse($manager->resume());

        // still no session
        $this->assertTrue(session_id() === '');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerateId()
    {
        $cookies = array();
        $manager = new SessionManager($cookies);

        $manager->start();
        $old_id = session_id();
        $this->assertTrue(session_id() !== '');

        $manager->regenerateId();
        $new_id = session_id();
        $this->assertTrue($old_id !== $new_id);
    }

    public function testWithCallables()
    {
        $cookies = array();
        $start = array($this, 'start');
        $resume = array($this, 'resume');
        $regenerate_id = array($this, 'regenerateId');
        $manager = new SessionManager(
            $cookies,
            $start,
            $resume,
            $regenerate_id
        );

        $manager->start();
        $this->assertTrue($this->start);

        $manager->resume();
        $this->assertTrue($this->resume);

        $manager->regenerateId();
        $this->assertTrue($this->regenerate_id);
    }

    public function start()
    {
        $this->start = true;
    }

    public function resume()
    {
        $this->resume = true;
    }

    public function regenerateId()
    {
        $this->regenerate_id = true;
    }
}

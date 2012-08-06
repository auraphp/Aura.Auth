<?php

namespace Aura\Auth;

use Aura\Auth\Adapter\MockAdapter;

require_once 'session_mock_functions.php';
require_once 'MockAdapter.php';

/*[
            'username' => 'jdoe',
            'full_name' => 'john doe',
            'email' => 'jdoe@example.com', 
            'uri'  => 'example.com',
            'avatar' => 'example.com/avatar.jpg',
            'unique_id' => 'jdoe'
        ]*/
class AuthTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        parent::setUp();

        // set to PHP defaults
        ini_set('session.gc_maxlifetime', 1440);
        ini_set('session.cookie_lifetime', 14400);

        // reset / unset GLOBALS
        $GLOBALS['session_regenerate_id'] = false;
        $GLOBALS['session_start']         = false;

        unset($GLOBALS['Aura\Auth']);
    }

    protected function newInstance()
    {
        return new Auth(
            new AdapterFactory([
                'mock' => new MockAdapter
            ])
        );
    }
    protected function setUpUserSession()
    {
        $array = [
            'username'  => 'jdoe',
            'full_name' => 'john doe',
            'email'     => 'jdoe@example.com', 
            'uri'       => 'example.com',
            'avatar'    => 'example.com/avatar.jpg',
            'unique_id' => 'jdoe'
        ];
        $usr = new User;
        $usr->setFromArray($array);

        $now = time() - 1;
        $_SESSION["Aura\Auth\Auth"]['user']    = $usr;
        $_SESSION["Aura\Auth\Auth"]['initial'] = $now;
        $_SESSION["Aura\Auth\Auth"]['active']  = $now;
    }

    public function test__constructStartsSession()
    {
        unset($_SESSION);

        $this->newInstance();

        $this->assertTrue($GLOBALS["session_start"]);
        $this->assertTrue($GLOBALS['session_regenerate_id']);
    }

    public function test__constructIdleException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $idle = ini_set('session.gc_maxlifetime', 5);
        $this->newInstance();
    }

    public function test__constructExpiredException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $idle = ini_set('session.cookie_lifetime', 5);
        $this->newInstance();
    }

    public function test__constructUpdatesIdleExpireActiveTime()
    {
        $this->setUpUserSession();

        $org  = $_SESSION["Aura\Auth\Auth"]['active'];
        $auth = $this->newInstance();
        $this->assertTrue($_SESSION["Aura\Auth\Auth"]['active'] > $org);
    }

    public function testGetUserIsEmpty()
    {
        $auth = $this->newInstance();
        $this->assertEmpty($auth->getUser());
        $this->assertTrue($GLOBALS['session_regenerate_id']);
    }

    public function testGetUser()
    {
        $this->setUpUserSession();

        $auth = $this->newInstance();
        $this->assertInstanceOf('\Aura\Auth\User', $auth->getUser());
        $this->assertTrue($GLOBALS['session_regenerate_id']);
    }

    public function testUpdateIdleExpireExpired()
    {
        $this->setUpUserSession();
        $auth = $this->newInstance();

        // set the time auth was initialized to 4 hours ago
        $_SESSION["Aura\Auth\Auth"]['initial'] -= 14401; // plus 1 - setUpUserSession = time() - 1
        $_SESSION["Aura\Auth\Auth"]['active']  -= 14401;


        $return = $auth->updateIdleExpire();
        $this->assertFalse($return);
        $this->assertFalse($auth->isValid());
        $this->assertSame(Auth::EXPIRED, $auth->getStatus());
    }

    public function testUpdateIdleExpireIdled()
    {
        $this->setUpUserSession();
        $auth = $this->newInstance();

        // set the time auth was initialized to 24 min ago
        $_SESSION["Aura\Auth\Auth"]['initial'] -= 1441; // plus 1 - setUpUserSession = time() - 1
        $_SESSION["Aura\Auth\Auth"]['active']  -= 1441;


        $return = $auth->updateIdleExpire();
        $this->assertFalse($return);
        $this->assertFalse($auth->isValid());
        $this->assertSame(Auth::IDLED, $auth->getStatus());
    }

    public function testTransitioningBetweenAnonymousAndAnonymous()
    {
        $auth = $this->newInstance();

        $this->assertNull($auth->reset(Auth::ANON));
    }

    public function testValidateMissingOptionException()
    {
        $this->setExpectedException('\Aura\Auth\Exception\MissingOption');

        $GLOBALS['Aura\Auth']['missing_option'] = true;
        $auth = $this->newInstance();

        $auth->validate('mock', []);
    }

    public function testValidateWrong()
    {
        $GLOBALS['Aura\Auth']['wrong'] = true;

        $auth   = $this->newInstance();
        $result = $auth->validate('mock', []);

        $this->assertFalse($result);
        $this->assertSame(Auth::WRONG, $auth->getStatus());
    }

    public function testValidate()
    {
        $auth   = $this->newInstance();
        $result = $auth->validate('mock', []);

        $this->assertTrue($result);
        $this->assertSame(Auth::VALID, $auth->getStatus());
    }
}
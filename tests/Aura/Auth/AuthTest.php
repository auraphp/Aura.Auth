<?php

namespace Aura\Auth;

use Aura\Auth\Adapter\MockAdapter;
use Aura\Session\Manager;
use Aura\Session\SegmentFactory;
use Aura\Session\CsrfTokenFactory;
use Aura\Session\MockSessionHandler;

require_once 'MockAdapter.php';


class AuthTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        parent::setUp();

        session_set_save_handler(new MockSessionHandler);
        // set to PHP defaults
        ini_set('session.gc_maxlifetime', 1440);
        ini_set('session.cookie_lifetime', 14400);
    }

    protected function newInstance($session = null)
    {
        if (! $session) {
            $session = new Manager(
                new SegmentFactory,
                new CsrfTokenFactory);
        }

        return new Auth(
            new AdapterFactory([
                'mock' => new MockAdapter
            ]),
            $session
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

        $now     = time() - 1;
        $session = new Manager(
            new SegmentFactory,
            new CsrfTokenFactory
        );

        $seg          = $session->getSegment('Aura\Auth\Auth');
        $seg->user    = $usr;
        $seg->initial = $now;
        $seg->active  = $now;

        return $session;
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
        $session = $this->setUpUserSession();
        $org     = $session->getSegment('Aura\Auth\Auth')->active;
        $auth    = $this->newInstance($session);

        $this->assertTrue($_SESSION["Aura\Auth\Auth"]['active'] > $org);
    }

    public function testGetUserIsEmpty()
    {
        $auth = $this->newInstance();
        $this->assertEmpty($auth->getUser());
    }

    public function testGetUser()
    {
        $this->setUpUserSession();

        $auth = $this->newInstance();
        $this->assertInstanceOf('\Aura\Auth\User', $auth->getUser());
    }

    public function testUpdateIdleExpireExpired()
    {
        $session = $this->setUpUserSession();
        $auth    = $this->newInstance($session);

        // set the time auth was initialized to 4 hours ago
        $session->getSegment('Aura\Auth\Auth')->initial -= 14401; // plus 1 - setUpUserSession = time() - 1
        $session->getSegment('Aura\Auth\Auth')->active  -= 14401;


        $return = $auth->updateIdleExpire();
        $this->assertFalse($return);
        $this->assertFalse($auth->isValid());
        $this->assertSame(Auth::EXPIRED, $auth->getStatus());
    }

    public function testUpdateIdleExpireIdled()
    {
        $session = $this->setUpUserSession();
        $auth = $this->newInstance($session);

        // set the time auth was initialized to 24 min ago
        $session->getSegment('Aura\Auth\Auth')->initial -= 1441; // plus 1 - setUpUserSession = time() - 1
        $session->getSegment('Aura\Auth\Auth')->active  -= 1441;


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
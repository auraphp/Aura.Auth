<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $session;

    protected $segment;

    protected $timer;

    protected $user;

    protected $auth;

    protected function setUp()
    {
        $this->adapter = new FakeAdapter(array(
            'boshag' => '123456',
        ));

        $this->session = new FakeSession;
        $this->segment = new FakeSegment;
        $this->timer = new Timer(1440, 14400);
        $this->user = new User(
            $this->session,
            $this->segment,
            $this->timer
        );

        $this->auth = new Auth(
            $this->adapter,
            $this->user
        );
    }

    public function testGetters()
    {
        $this->assertSame($this->adapter, $this->auth->getAdapter());
        $this->assertSame($this->user, $this->auth->getUser());
    }

    public function testLogin()
    {
        $this->assertTrue($this->user->isAnon());

        $this->assertTrue($this->auth->login(array(
            'username' => 'boshag',
            'password' => '123456',
        )));

        $this->assertTrue($this->user->isValid());
    }

    public function testLogin_error()
    {
        $this->assertTrue($this->user->isAnon());

        $this->assertFalse($this->auth->login(array(
            'username' => 'boshag',
            'password' => '------',
        )));

        $this->assertTrue($this->user->isAnon());

        $this->assertSame(
            $this->adapter->getError(),
            $this->auth->getError()
        );
    }

    public function testLogout()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->auth->logout();
        $this->assertTrue($this->user->isAnon());
    }

    public function testLogout_error()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag', array(
            'logout_error' => true,
        ));
        $this->assertTrue($this->user->isValid());

        // cannot log out, stays valid
        $this->assertFalse($this->auth->logout());
        $this->assertTrue($this->user->isValid());

        $this->assertSame(
            $this->adapter->getError(),
            $this->auth->getError()
        );
    }

    public function testInit()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 100);
        $this->auth->init();
        $this->assertTrue($this->user->isValid());
        $this->assertSame(time(), $this->user->getLastActive());
    }

    public function testInit_cannotResume()
    {
        $this->session->allow_resume = false;
        $this->assertTrue($this->user->isAnon());
        $this->auth->init();
        $this->assertTrue($this->user->isAnon());
    }

    public function testInit_logoutIdle()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setLastActive(time() - 1441);

        $this->auth->init();
        $this->assertTrue($this->user->isIdle());
        $this->assertNull($this->user->getName());
    }

    public function testInit_logoutExpired()
    {
        $this->assertTrue($this->user->isAnon());
        $this->user->forceLogin('boshag');
        $this->assertTrue($this->user->isValid());

        $this->user->setFirstActive(time() - 14441);

        $this->auth->init();
        $this->assertTrue($this->user->isExpired());
        $this->assertNull($this->user->getName());
    }
}

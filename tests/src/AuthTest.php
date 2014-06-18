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

    protected function assertAnon()
    {
        $this->assertTrue($this->user->isAnon());
        $this->assertFalse($this->user->isValid());
        $this->assertNull($this->auth->getUser());
        $this->assertSame(array(), $this->auth->getInfo());
        $this->assertNull($this->auth->getFirstActive());
        $this->assertNull($this->auth->getLastActive());
    }

    protected function assertValid()
    {
        $now = time();
        $this->assertFalse($this->user->isAnon());
        $this->assertTrue($this->user->isValid());
        $this->assertSame('boshag', $this->auth->getUser());
        $this->assertSame(array('foo' => 'bar'), $this->auth->getInfo());
        $this->assertSame($now, $this->auth->getFirstActive());
        $this->assertSame($now, $this->auth->getLastActive());
    }
}

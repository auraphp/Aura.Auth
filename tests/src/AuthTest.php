<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected $auth;

    protected $session;

    protected $segment;

    protected $adapter;

    protected $timer;

    protected function setUp()
    {
        $this->adapter = new FakeAdapter(array(
            'boshag' => '123456',
        ));
        $this->session = new FakeSession;
        $this->segment = new FakeSegment;
        $this->timer = new Timer(1440, 14400);
        $this->auth = new Auth(
            $this->adapter,
            $this->session,
            $this->segment,
            $this->timer
        );
    }

    public function testForceLoginAndLogout()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertValid();

        $this->auth->forceLogout();
        $this->assertAnon();
    }

    public function testLogin()
    {
        $this->assertTrue($this->auth->isAnon());

        $this->assertTrue($this->auth->login(array(
            'username' => 'boshag',
            'password' => '123456',
        )));

        $this->assertTrue($this->auth->isValid());
    }

    public function testLogin_error()
    {
        $this->assertTrue($this->auth->isAnon());

        $this->assertFalse($this->auth->login(array(
            'username' => 'boshag',
            'password' => '------',
        )));

        $this->assertTrue($this->auth->isAnon());

        $this->assertSame(
            $this->adapter->getError(),
            $this->auth->getError()
        );
    }

    public function testLogout()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->auth->forceLogin('boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->logout();
        $this->assertTrue($this->auth->isAnon());
    }

    public function testLogout_error()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->auth->forceLogin('boshag', array(
            'logout_error' => true,
        ));
        $this->assertTrue($this->auth->isValid());

        $this->auth->logout();
        $this->assertTrue($this->auth->isValid());

        $this->assertSame(
            $this->adapter->getError(),
            $this->auth->getError()
        );
    }

    public function testRefresh()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->auth->isValid());

        $this->segment->active -= 100;
        $this->assertSame(time() - 100, $this->auth->getActive());

        $this->auth->refresh();
        $this->assertSame(time(), $this->auth->getActive());
    }

    public function testRefresh_idle()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->auth->isValid());

        $this->segment->active -= 1441;
        $this->auth->refresh();
        $this->assertTrue($this->auth->isIdle());
    }

    public function testRefresh_expired()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertValid();

        $this->segment->initial -= 14441;
        $this->auth->refresh();
        $this->assertTrue($this->auth->isExpired());
    }

    protected function assertAnon()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->assertFalse($this->auth->isValid());
        $this->assertNull($this->auth->getUser());
        $this->assertSame(array(), $this->auth->getInfo());
        $this->assertNull($this->auth->getInitial());
        $this->assertNull($this->auth->getActive());
    }

    protected function assertValid()
    {
        $now = time();
        $this->assertFalse($this->auth->isAnon());
        $this->assertTrue($this->auth->isValid());
        $this->assertSame('boshag', $this->auth->getUser());
        $this->assertSame(array('foo' => 'bar'), $this->auth->getInfo());
        $this->assertSame($now, $this->auth->getInitial());
        $this->assertSame($now, $this->auth->getActive());
    }
}

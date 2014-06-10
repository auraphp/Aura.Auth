<?php
namespace Aura\Auth;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected $auth;

    protected $adapter;

    protected $session;

    protected $timer;

    protected function setUp()
    {
        $this->adapter = new FakeAdapter(array('boshag' => '123456'));
        $this->session = new FakeSession;
        $this->timer = new Timer(1440, 14400);
        $this->auth = new Auth($this->adapter, $this->session, $this->timer);
    }

    public function testGetProperties()
    {
        $this->assertSame($this->adapter, $this->auth->getAdapter());
        $this->assertSame($this->session, $this->auth->getSession());
        $this->assertSame($this->timer, $this->auth->getTimer());
    }

    public function testLoginAndLogout()
    {
        $this->assertAnon();

        $this->auth->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertValid();

        $this->auth->logout();
        $this->assertAnon();
    }

    public function testLogin_bad()
    {
        $this->assertAnon();

        $this->auth->login(array(
            'username' => 'boshag',
            'password' => '------',
        ));

        $this->assertSame(Auth::ERROR, $this->auth->getStatus());
        $expect = 'Password mismatch.';
        $actual = $this->auth->getError();
        $this->assertSame($expect, $actual);
    }

    public function testLogout_bad()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array(
            'adapter' => 'fake',
            'logout_error' => true,
        ));
        $this->assertValid();

        $this->auth->logout();
        $this->assertSame(Auth::ERROR, $this->auth->getStatus());
        $expect = 'Triggered logout error.';
        $actual = $this->auth->getError();
        $this->assertSame($expect, $actual);
    }

    public function testUpdateActive()
    {
        $this->assertAnon();

        $this->auth->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertValid();

        $this->session->active -= 100;
        $this->assertSame(time() - 100, $this->auth->getActive());

        $this->auth->updateActive();
        $this->assertSame(time(), $this->auth->getActive());
    }

    public function testUpdateActive_idled()
    {
        $this->assertAnon();

        $this->auth->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertValid();

        $this->session->active -= 1441;

        $this->auth->updateActive();
        $this->assertAnon();

        $this->assertSame(Auth::IDLED, $this->auth->getStatus());
    }

    public function testUpdateActive_expired()
    {
        $this->assertAnon();

        $this->auth->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertValid();

        $this->session->initial -= 14441;

        $this->auth->updateActive();
        $this->assertAnon();

        $this->assertSame(Auth::EXPIRED, $this->auth->getStatus());
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

        $info = $this->auth->getInfo();
        $this->assertSame('fake', $info['adapter']);

        $this->assertSame($now, $this->auth->getInitial());
        $this->assertSame($now, $this->auth->getActive());
    }

}

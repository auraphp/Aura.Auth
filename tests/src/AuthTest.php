<?php
namespace Aura\Auth;

use Aura\Auth\Session\FakeSessionManager;
use Aura\Auth\Session\SessionDataObject;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected $auth;

    protected $manager;

    protected $data;

    protected $object;

    protected $timer;

    protected function setUp()
    {
        $this->object = (object) array();

        $this->manager = new FakeSessionManager;
        $this->data = new SessionDataObject($this->object);
        $this->timer = new Timer(1440, 14400);
        $this->auth = new Auth($this->manager, $this->data, $this->timer);
    }

    public function testForceLoginAndLogout()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertValid();

        $this->auth->forceLogout();
        $this->assertAnon();
    }

    public function testRefresh()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->auth->isValid());

        $this->data->active -= 100;
        $this->assertSame(time() - 100, $this->auth->getActive());

        $this->auth->refresh();
        $this->assertSame(time(), $this->auth->getActive());
    }

    public function testRefresh_idle()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertTrue($this->auth->isValid());

        $this->data->active -= 1441;
        $this->auth->refresh();
        $this->assertTrue($this->auth->isIdle());
    }

    public function testRefresh_expired()
    {
        $this->assertAnon();

        $this->auth->forceLogin('boshag', array('foo' => 'bar'));
        $this->assertValid();

        $this->data->initial -= 14441;
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

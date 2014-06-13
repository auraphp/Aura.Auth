<?php
namespace Aura\Auth\Handler;

use Aura\Auth\Auth;
use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSessionManager;
use Aura\Auth\Session\SessionDataObject;
use Aura\Auth\Timer;

abstract class AbstractHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $auth;

    protected $manager;

    protected $data;

    protected $object;

    protected $timer;

    protected $handler;

    protected function setUp()
    {
        $this->object = (object) array();

        $this->manager = new FakeSessionManager;
        $this->data = new SessionDataObject($this->object);
        $this->timer = new Timer(1440, 14400);
        $this->auth = new Auth($this->manager, $this->data, $this->timer);

        $this->adapter = new FakeAdapter(array(
            'boshag' => '123456',
        ));
    }

    public function testGetAuth()
    {
        $this->assertSame($this->auth, $this->handler->getAuth());
    }

    public function testGetAdapter()
    {
        $this->assertSame($this->adapter, $this->handler->getAdapter());
    }
}

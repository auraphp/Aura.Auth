<?php
namespace Aura\Auth;

use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Status;

class UserTest extends \PHPUnit_Framework_TestCase
{
    protected $user;

    protected $segment;

    protected function setUp()
    {
        $this->segment = new FakeSegment;
        $this->user = new User($this->segment);
    }

    public function test()
    {
        $now = time();
        $this->user->set(
            Status::VALID,
            $now,
            $now,
            'boshag',
            array('foo' => 'bar')
        );

        $this->assertSame(Status::VALID, $this->user->getStatus());
        $this->assertSame($now, $this->user->getFirstActive());
        $this->assertSame($now, $this->user->getLastActive());
        $this->assertSame('boshag', $this->user->getName());
        $this->assertSame(array('foo' => 'bar'), $this->user->getData());
    }
}

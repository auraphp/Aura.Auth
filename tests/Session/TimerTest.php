<?php
namespace Aura\Auth\Session;

use Aura\Auth\Status;

class TimerTest extends \PHPUnit\Framework\TestCase
{
    protected $timer;

    protected function setUp() : void
    {
        $this->timer = new Timer(3600, 86400);
    }

    public function testHasExpired()
    {
        $this->assertFalse($this->timer->hasExpired(time()));
        $this->assertTrue($this->timer->hasExpired(time() - 86401));
    }

    public function testHasIdled()
    {
        $this->assertFalse($this->timer->hasIdled(time()));
        $this->assertTrue($this->timer->hasIdled(time() - 3601));
    }

    public function testGetTimeoutStatus()
    {
        $actual = $this->timer->getTimeoutStatus(
            time() - 86401,
            time()
        );
        $this->assertSame(Status::EXPIRED, $actual);

        $actual = $this->timer->getTimeoutStatus(
            time() - 3602,
            time() - 3601
        );

        $this->assertSame(Status::IDLE, $actual);

        $this->assertNull($this->timer->getTimeoutStatus(
            time(),
            time()
        ));
    }

    public function testSetIdleTtl_bad()
    {
        $this->expectException('Aura\Auth\Exception');
        $this->timer->setIdleTtl(3601);
    }

    public function testSetExpireTtl_bad()
    {
        $this->expectException('Aura\Auth\Exception');
        $this->timer->setExpireTtl(86401);
    }
}

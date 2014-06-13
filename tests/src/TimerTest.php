<?php
namespace Aura\Auth;

class TimerTest extends \PHPUnit_Framework_TestCase
{
    protected $timer;

    protected function setUp()
    {
        $this->timer = new Timer(1440, 14400);
    }

    public function testHasExpired()
    {
        $this->assertFalse($this->timer->hasExpired(time()));
        $this->assertTrue($this->timer->hasExpired(time() - 14441));
    }

    public function testHasIdled()
    {
        $this->assertFalse($this->timer->hasIdled(time()));
        $this->assertTrue($this->timer->hasIdled(time() - 1441));
    }

    public function testSetIdleTtl_bad()
    {
        $this->setExpectedException('Aura\Auth\Exception');
        $this->timer->setIdleTtl(1441);
    }

    public function testSetExpireTtl_bad()
    {
        $this->setExpectedException('Aura\Auth\Exception');
        $this->timer->setExpireTtl(14441);
    }
}

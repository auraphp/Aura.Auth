<?php
namespace Aura\Auth\Session;

class SegmentTest extends \PHPUnit_Framework_TestCase
{
    protected $segment;

    public function setUp()
    {
        $this->segment = new Segment(__CLASS__);
    }

    public function testWithoutSession()
    {
        $this->segment->foo = 'bar';
        $this->assertNull($this->segment->foo);
    }

    /**
     * @runInSeparateProcess
     */
    public function testWithSession()
    {
        session_start();
        $this->assertFalse(isset($this->segment->foo));
        $this->assertNull($this->segment->foo);
        $this->segment->foo = 'bar';
        $this->assertTrue(isset($this->segment->foo));
        $this->assertSame('bar', $this->segment->foo);
        $this->assertSame('bar', $_SESSION[__CLASS__]['foo']);
        unset($this->segment->foo);
        $this->assertFalse(isset($this->segment->foo));
    }
}

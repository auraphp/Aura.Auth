<?php
namespace Aura\Auth\Session;

class SegmentTest extends \PHPUnit\Framework\TestCase
{
    protected $segment;

    public function setUp() : void
    {
        $this->segment = new Segment(__CLASS__);
    }

    public function testWithoutSession()
    {
        $this->segment->set('foo', 'bar');
        $this->assertNull($this->segment->get('foo'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testWithSession()
    {
        session_start();
        $this->assertNull($this->segment->get('foo'));
        $this->segment->set('foo', 'bar');
        $this->assertSame('bar', $this->segment->get('foo'));
        $this->assertSame('bar', $_SESSION[__CLASS__]['foo']);
    }
}

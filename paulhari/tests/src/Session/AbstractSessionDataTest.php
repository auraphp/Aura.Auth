<?php
namespace Aura\Auth\Session;

abstract class AbstractSessionDataTest extends \PHPUnit_Framework_TestCase
{
    protected $data;

    public function test()
    {
        $this->assertFalse(isset($this->data->foo));
        $this->assertNull($this->data->foo);
        $this->data->foo = 'bar';
        $this->assertTrue(isset($this->data->foo));
        $this->assertSame('bar', $this->data->foo);
        $this->assertSame('bar', $this->getData('foo'));
        unset($this->data->foo);
        $this->assertFalse(isset($this->data->foo));
    }
}

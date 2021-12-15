<?php
namespace Aura\Auth\Adapter;

class NullAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $adapter;

    protected function setUp() : void
    {
        $this->adapter = new NullAdapter;
    }

    public function testLogin()
    {
        list($name, $data) = $this->adapter->login(array());
        $this->assertNull($name);
        $this->assertNull($data);
    }
}

<?php
namespace Aura\Auth;

class FunctionProxyTest extends \PHPUnit_Framework_TestCase
{
    protected $proxy;

    protected function setUp()
    {
        $this->proxy = new FunctionProxy;
    }

    public function testInstance()
    {
        $this->assertEquals(
            str_replace('Hello', 'Hi', 'Hello Aura'),
            $this->proxy->str_replace('Hello', 'Hi', 'Hello Aura')
        );
    }
}

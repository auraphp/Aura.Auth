<?php
namespace Aura\Auth;

class ExtensionProxyTest extends \PHPUnit_Framework_TestCase
{
    protected $proxy;

    protected function setUp()
    {
        $this->proxy = new ExtensionProxy('str');
    }

    public function testInstance()
    {
        $this->assertEquals(
            str_replace('Hello', 'Hi', 'Hello Aura'),
            $this->proxy->replace('Hello', 'Hi', 'Hello Aura')
        );
    }
}

<?php

namespace Aura\Auth;


class AdapterFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionWhenAdaperNotFound()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $factory = new AdapterFactory([]);
        $factory->newInstance('invalid');
    }

    public function testGetAdaper()
    {
        $factory = new AdapterFactory([
            'test' => 'test_adapter'
        ]);

        $return = $factory->newInstance('test');
        $this->assertSame('test_adapter', $return);
    }

    public function testAdaperClosure()
    {
        $factory = new AdapterFactory([
            'test' => function () {
                return 'test_adapter';
            }
        ]);

        $return = $factory->newInstance('test');
        $this->assertSame('test_adapter', $return);
    }
}
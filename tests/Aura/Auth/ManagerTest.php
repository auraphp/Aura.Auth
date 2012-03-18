<?php

namespace Aura\Auth;


class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function test__constructAddAdapersAsArgument()
    {
        $adapter1 = $this->getMock('\Aura\Auth\Adapter\AuthInterface');
        
        $adapter1->expects($this->once())
                 ->method('authenticate')
                 ->will($this->returnValue('adapter1'));

        $adapter2 = $this->getMock('\Aura\Auth\Adapter\AuthInterface');
        
        $adapter2->expects($this->once())
                 ->method('authenticate')
                 ->will($this->returnValue('adapter2'));

        $manager = new Manager([
            'adapter1' => function () use ($adapter1) {
                return $adapter1;
            },
            'adapter2' => function () use ($adapter2) {
                return $adapter2;
            },
        ]);

        $this->assertEquals('adapter1', $manager->authenticate('adapter1'));
        $this->assertEquals('adapter2', $manager->authenticate('adapter2'));
    }

    public function testSetAdapter()
    {
        $adapter = $this->getMock('\Aura\Auth\Adapter\AuthInterface');
        
        $adapter->expects($this->once())
                ->method('authenticate')
                ->will($this->returnValue('adapter'));

        $manager = new Manager;
        $manager->setAdapter('adapter', $adapter);

        $this->assertEquals('adapter', $manager->authenticate('adapter'));
    }

    public function testAuthenticateException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $manager = new Manager;

        $manager->authenticate('doesnot_exist');
    }
}
<?php

namespace Aura\Auth\Adapter;

use Aura\Auth\User;

require_once 'MockClosure.php';

class ClosureTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $this->object = new Closure(new User, new MockClosure);
    }
    
    public function test__constructNotClosureException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        new Closure(new User, []);
    }
    
    public function test__constructNotClosureException2()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        new Closure(new User, new MockClosure_NoInvoke);
    }
    
    public function testAuthenticateMissingUsernameException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $this->object->authenticate(['password' => '12345']);
    }
    
    public function testAuthenticateMissingPasswordException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $this->object->authenticate(['username' => 'janedoe']);
    }
    
    public function testAuthenticateFalseOnEmptyUsername()
    {
        $result = $this->object
                       ->authenticate(['username' => '', 'password' => '12345']);

        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnEmptyPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe', 'password' => '']);

        $this->assertFalse($result);
    }

    public function testAuthenticate()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);  

        $result = $this->object
                       ->authenticate(['username' => 'johndoe', 'password' => '12345']);

        $this->assertFalse($result);   
    }

    public function testAuthenticateExceptionOnInvaildReturnType()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $closure = new Closure(new User, function ($username, $password) {
            return true;
        });

        $result = $closure->authenticate(['username' => 'john', 'password' => '12345']);
    }
}
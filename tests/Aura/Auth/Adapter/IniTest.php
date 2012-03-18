<?php

namespace Aura\Auth\Adapter;

use Aura\Auth\User;

class IniTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $file = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'test.ini';
        $this->object = new Ini(new User, $file);
    }
    
    public function test__constructException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        new Ini(new User, '/file/doesnot/exist');
    }
    
    public function testAuthenticateMissingUsernameException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $this->object->authenticate(['password' => '12345']);
    }
    
    public function testUnknownAlgorlthmException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $this->object->authenticate(['username' => 'janedoe2', 'password' => '12345']);
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
    
    public function testAuthenticateFalseOnEmptyIniPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe', 'password' => '12345']);

        $this->assertFalse($result);
    }

    public function testPlainTextPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'johndoe', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);     
    }

    public function testInvalidPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'johndoe', 'password' => '1234']);

        $this->assertFalse($result);     
    }

    public function testHashedPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'johndoe2', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);   
    }

    public function testHashedAndSaltedPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'johndoe3', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);   
    }
}
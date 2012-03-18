<?php

namespace Aura\Auth\Adapter;

use Aura\Auth\User;

class HtpasswdTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        $file = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'htpasswd';
        $this->object = new Htpasswd(new User, $file);
    }
    
    public function test__constructException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        new Htpasswd(new User, '/file/doesnot/exist');
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
    
    public function testAuthenticateFopenFailedException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $GLOBALS['fopen'] = false;
        
        $this->object->authenticate(['username' => 'janedoe', 'password' => '12345']);
    }
    
    public function testAuthenticateFalseOnEmptyUsername()
    {
        $result = $this->object
                       ->authenticate(['username' => '', 'password' => '12345']);

        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnUsernameNotFound()
    {
        $result = $this->object
                       ->authenticate(['username' => 'not-here', 'password' => '12345']);

        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnEmptyPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe', 'password' => '']);

        $this->assertFalse($result);
    }

    public function testApr1Password()
    {
        $result = $this->object
                       ->authenticate(['username' => 'johndoe', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);     
    }

    public function testShaPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);   
    }

    public function testDesPassword()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe2', 'password' => '12345']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);   
    }

    public function testInvalidPasswordReturnsFalse()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe2', 'password' => '1234']);

        $this->assertFalse($result);   
    }

    public function testDesPasswordLongerThan8ReturnsFalse()
    {
        $result = $this->object
                       ->authenticate(['username' => 'janedoe3', 'password' => '123456789']);

        $this->assertFalse($result);   
    }
}
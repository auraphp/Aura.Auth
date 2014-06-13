<?php

namespace Aura\Auth\Adapter;

class HtpasswdTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        parent::setUp();
        $file = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'htpasswd';
        $this->object = new Htpasswd($file);
    }
    
    public function test__constructException()
    {
        $this->setExpectedException('\Aura\Auth\Exception\FileDoesNotExists');

        new Htpasswd('/file/doesnot/exist');
    }
    
    public function testAuthenticateMissingUsernameException()
    {
        $this->setExpectedException('\Aura\Auth\Exception\MissingUsernameOrPassword');
        $this->object->setPassword('12345');
        $this->object->authenticate();
    }
    
    public function testAuthenticateMissingPasswordException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');
        $this->object->setUsername('janedoe');
        $this->object->authenticate();
    }
    
    public function testAuthenticateFopenFailedException()
    {
        // $this->setExpectedException('\Aura\Auth\Exception\FileNotReadable');

        // $GLOBALS['fopen'] = false;
        // $this->object->setUsername('janedoe');
        // $this->object->setPassword('12345');
        // $this->assertFalse($this->object->authenticate());
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
    
    public function testAuthenticateFalseOnEmptyUsername()
    {
        $this->object->setUsername('');
        $this->object->setPassword('12345');
        $result = $this->object
                       ->authenticate();

        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnUsernameNotFound()
    {
        $this->object->setUsername('not-here');
        $this->object->setPassword('12345');
        $result = $this->object->authenticate();
        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnEmptyPassword()
    {
        $this->object->setUsername('janedoe');
        $this->object->setPassword('');
        $result = $this->object->authenticate();

        $this->assertFalse($result);
    }

    public function testApr1Password()
    {
        $this->object->setUsername('johndoe');
        $this->object->setPassword('12345');
        $result = $this->object->authenticate();

        $this->assertTrue($result);
    }

    public function testShaPassword()
    {
        $this->object->setUsername('johndoe');
        $this->object->setPassword('12345');
        $result = $this->object->authenticate();

        $this->assertTrue($result);
    }

    public function testDesPassword()
    {
        $this->object->setUsername('janedoe2');
        $this->object->setPassword('12345');
        $result = $this->object->authenticate();

        $this->assertTrue($result);
    }

    public function testInvalidPasswordReturnsFalse()
    {
        $this->object->setUsername('janedoe2');
        $this->object->setPassword('wrong-password');
        $result = $this->object->authenticate();

        $this->assertFalse($result);
    }

    public function testDesPasswordLongerThan8ReturnsFalse()
    {
        $this->object->setUsername('janedoe2');
        $this->object->setPassword('123456789');
        $result = $this->object->authenticate();

        $this->assertFalse($result);
    }
}

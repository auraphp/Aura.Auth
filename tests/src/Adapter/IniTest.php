<?php
/**
 * 
 * This file is part of Aura for PHP.
 * 
 * @package Aura.Auth
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth\Adapter;

class IniTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        parent::setUp();
        $file = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'auth.ini';
        $this->object = new Ini($file);        
    }
    
    public function test__constructException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');
        new Ini('/file/doesnot/exist');
    }
    
    public function testAuthenticateMissingUsernameException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');

        $this->object->authenticate();
    }
    
    public function testUnknownAlgorlthmException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');
        $this->object->setUsername('unknownalgorithm');
        $this->object->setPassword('john');
        $this->object->authenticate();
    }
    
    public function testAuthenticateMissingPasswordException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');
        $this->object->setUsername('john');
        $this->object->authenticate();
    }
    
    public function testAuthenticateFalseOnEmptyUsername()
    {
        $this->object->setUsername('john');
        $this->object->setPassword('john');
        $result = $this->object
                       ->authenticate();        
        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnEmptyPassword()
    {
        $this->object->setUsername('john');
        $this->object->setPassword('');
        $result = $this->object
                       ->authenticate();

        $this->assertFalse($result);
    }
    
    public function testAuthenticateFalseOnEmptyIniPassword()
    {
        $this->object->setUsername('john');
        $this->object->setPassword('');
        $result = $this->object
                       ->authenticate();

        $this->assertFalse($result);
    }

    public function testPlainTextPassword()
    {
        $this->object->setUsername('plain');
        $this->object->setPassword('plain');
        $result = $this->object
                       ->authenticate();

        $this->assertTrue($result);
    }

    public function testInvalidPassword()
    {
        $this->object->setUsername('plain');
        $this->object->setPassword('invalid');
        $result = $this->object
                       ->authenticate();

        $this->assertFalse($result);
    }

    public function testHashedPassword()
    {
        $this->object->setUsername('hashed');
        $this->object->setPassword('12345');
        $result = $this->object
                       ->authenticate();

        $this->assertTrue($result);
    }

    public function testHashedAndSaltedPassword()
    {
        $this->object->setUsername('john');
        $this->object->setPassword('12345');
        $result = $this->object
                       ->authenticate();

        $this->assertTrue($result);
    }
}

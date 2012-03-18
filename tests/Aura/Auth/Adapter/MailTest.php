<?php

namespace Aura\Auth\Adapter;

use Aura\Auth\User;

require_once 'mock_functions.php';

class MailTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        parent::setUp();

        if (empty($GLOBALS['Aura\Auth\Adapter\Mail']['mailbox'])
            || empty($GLOBALS['Aura\Auth\Adapter\Mail']['username'])
            || empty($GLOBALS['Aura\Auth\Adapter\Mail']['password'])) {

            $this->markTestSkipped('The mailbox, username and password have not been setup');
        }

        $this->object = new Mail(new User, $GLOBALS['Aura\Auth\Adapter\Mail']['mailbox']);
    }
    
    public function test__constructException()
    {
        $this->setExpectedException('\Aura\Auth\Exception');
        $GLOBALS['extension_loaded'] = false;
        new Mail(new User, '');
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
                       ->authenticate($GLOBALS['Aura\Auth\Adapter\Mail']);

        $this->assertInstanceOf('\Aura\Auth\User', $result);   
    }
}
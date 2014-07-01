<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\FunctionProxy;

class LdapAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $proxy;

    protected function setUp()
    {
        $this->proxy = $this->getMock(
            'Aura\Auth\FunctionProxy',
            array(
                'ldap_connect',
                'ldap_bind',
                'ldap_set_option',
                'ldap_close',
                'ldap_errno',
                'ldap_error'
            ),
            array()
        );
        $this->adapter = new LdapAdapter($this->proxy, 'ldap.example.com', '');
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Aura\Auth\Adapter\LdapAdapter',
            $this->adapter
        );
    }

    /**
     *
     * @expectedException Aura\Auth\Exception\ConnectionFailed
     *
     */
    public function testLoginConnectionFailed()
    {
        $cred = array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        );
        $this->proxy->expects($this->once())
            ->method('ldap_connect')
            ->with('ldap.example.com')
            ->will($this->returnValue(false));
        $this->adapter->login($cred);
    }

    public function testLoginSuccess()
    {
        $this->proxy->expects($this->once())
            ->method('ldap_connect')
            ->with('ldap.example.com')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->any())
            ->method('ldap_set_option')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->once())
            ->method('ldap_bind')
            ->will($this->returnValue(true));

        $cred = array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        );
        $this->assertEquals(array('username' => $cred['username']), $this->adapter->login($cred));
    }

    /**
     *
     * @expectedException Aura\Auth\Exception\ConnectionFailed
     *
     */
    public function testUsernamePasswordFailure()
    {
        $this->proxy->expects($this->once())
            ->method('ldap_connect')
            ->with('ldap.example.com')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->any())
            ->method('ldap_set_option')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->once())
            ->method('ldap_bind')
            ->will($this->returnValue(false));

        $this->proxy->expects($this->once())
            ->method('ldap_error')
            ->will($this->returnValue(400));

        $this->proxy->expects($this->once())
            ->method('ldap_errno')
            ->will($this->returnValue(500));

        $this->proxy->expects($this->once())
            ->method('ldap_close')
            ->will($this->returnValue(true));

        $cred = array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        );
        $this->adapter->login($cred);
    }
}

<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\ExtensionProxy;

class LdapAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $proxy;

    protected function setUp()
    {
        $this->proxy = $this->getMock(
            'Aura\Auth\ExtensionProxy',
            array('connect', 'bind', 'set_option', 'close', 'errno', 'error'),
            array('ldap')
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
            ->method('connect')
            ->with('ldap.example.com')
            ->will($this->returnValue(false));
        $this->adapter->login($cred);
    }

    public function testLoginSuccess()
    {
        $this->proxy->expects($this->once())
            ->method('connect')
            ->with('ldap.example.com')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->any())
            ->method('set_option')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->once())
            ->method('bind')
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
            ->method('connect')
            ->with('ldap.example.com')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->any())
            ->method('set_option')
            ->will($this->returnValue(true));

        $this->proxy->expects($this->once())
            ->method('bind')
            ->will($this->returnValue(false));

        $this->proxy->expects($this->once())
            ->method('error')
            ->will($this->returnValue(400));

        $this->proxy->expects($this->once())
            ->method('errno')
            ->will($this->returnValue(500));

        $this->proxy->expects($this->once())
            ->method('close')
            ->will($this->returnValue(true));

        $cred = array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        );
        $this->adapter->login($cred);
    }
}

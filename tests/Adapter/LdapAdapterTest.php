<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

class LdapAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $adapter;

    protected $phpfunc;

    protected function setUp() : void
    {
        $this->phpfunc = $this->getMockBuilder(FakeLdapPhpfunc::class)
             ->onlyMethods(array(
                 'ldap_connect',
                 'ldap_bind',
                 'ldap_unbind',
                 'ldap_set_option',
                 'ldap_errno',
                 'ldap_error'
             ))
             ->getMock();

        $this->adapter = new LdapAdapter(
            $this->phpfunc,
            'ldaps://ldap.example.com:636',
            'ou=Foo,dc=Bar,cn=users,uid=%s',
            array('LDAP_OPTION_KEY', 'LDAP_OPTION_VALUE')
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Aura\Auth\Adapter\LdapAdapter',
            $this->adapter
        );
    }

    public function testLogin()
    {
        $this->phpfunc->expects($this->once())
            ->method('ldap_connect')
            ->with('ldaps://ldap.example.com:636')
            ->willReturn(true);

        $this->phpfunc->expects($this->any())
            ->method('ldap_set_option')
            ->willReturn(true);

        $this->phpfunc->expects($this->once())
            ->method('ldap_bind')
            ->with(
                true,
                'ou=Foo,dc=Bar,cn=users,uid=someusername',
                'secretpassword'
            )
            ->willReturn(true);

        $this->phpfunc->expects($this->once())
            ->method('ldap_unbind')
            ->willReturn(true);

        $actual = $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));

        $this->assertEquals(
            array('someusername', array()),
            $actual
        );
    }

    public function testLogin_connectionFailed()
    {
        $input = array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        );
        $this->phpfunc->expects($this->once())
            ->method('ldap_connect')
            ->with('ldaps://ldap.example.com:636')
            ->willReturn(false);

        $this->expectException('Aura\Auth\Exception\ConnectionFailed');
        $this->adapter->login($input);
    }

    public function testLogin_bindFailed()
    {
        $this->phpfunc->expects($this->once())
            ->method('ldap_connect')
            ->with('ldaps://ldap.example.com:636')
            ->willReturn(true);

        $this->phpfunc->expects($this->any())
            ->method('ldap_set_option')
            ->willReturn(true);

        $this->phpfunc->expects($this->once())
            ->method('ldap_bind')
            ->willReturn(false);

        $this->phpfunc->expects($this->once())
            ->method('ldap_errno')
            ->willReturn(1);

        $this->phpfunc->expects($this->once())
            ->method('ldap_error')
            ->willReturn('Operations Error');

        $this->phpfunc->expects($this->once())
            ->method('ldap_unbind')
            ->willReturn(true);

        $this->expectException('Aura\Auth\Exception\BindFailed');
        $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));
    }
}

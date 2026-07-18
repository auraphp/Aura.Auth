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
                 'ldap_error',
                 'ldap_search',
                 'ldap_get_entries'
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

    protected function newSearchAdapter()
    {
        return new LdapAdapter(
            $this->phpfunc,
            'ldaps://ldap.example.com:636',
            'uid=%s,dc=Bar',
            array(),
            array(
                'binddn' => 'cn=service,dc=Bar',
                'bindpw' => 'servicepass',
                'basedn' => 'dc=Bar',
                'filter' => '(uid=%s)',
            )
        );
    }

    protected function fakeEntries()
    {
        return array(
            'count' => 1,
            0 => array(
                'count' => 2,
                0 => 'cn',
                1 => 'mail',
                'cn' => array('count' => 1, 0 => 'Alice Employee'),
                'mail' => array('count' => 1, 0 => 'alice@example.org'),
                'dn' => 'uid=alice,ou=people,dc=Bar',
            ),
        );
    }

    public function testLogin_searchAndBind()
    {
        $this->phpfunc->method('ldap_connect')->willReturn(true);
        $this->phpfunc->method('ldap_set_option')->willReturn(true);

        // first bind is the service account, second is the found user DN
        $this->phpfunc->expects($this->exactly(2))
            ->method('ldap_bind')
            ->willReturn(true);

        $this->phpfunc->expects($this->once())
            ->method('ldap_search')
            ->with(true, 'dc=Bar', '(uid=alice)', array())
            ->willReturn('result-resource');

        $this->phpfunc->expects($this->once())
            ->method('ldap_get_entries')
            ->with(true, 'result-resource')
            ->willReturn($this->fakeEntries());

        $actual = $this->newSearchAdapter()->login(array(
            'username' => 'alice',
            'password' => 'secretpassword'
        ));

        $this->assertEquals(
            array(
                'alice',
                array('cn' => 'Alice Employee', 'mail' => 'alice@example.org'),
            ),
            $actual
        );
    }

    public function testLogin_searchServiceBindFailed()
    {
        $this->phpfunc->method('ldap_connect')->willReturn(true);
        $this->phpfunc->expects($this->once())
            ->method('ldap_bind')
            ->willReturn(false);
        $this->phpfunc->method('ldap_errno')->willReturn(49);
        $this->phpfunc->method('ldap_error')->willReturn('Invalid credentials');

        $this->expectException('Aura\Auth\Exception\BindFailed');
        $this->newSearchAdapter()->login(array(
            'username' => 'alice',
            'password' => 'secretpassword'
        ));
    }

    public function testLogin_searchUsernameNotFound()
    {
        $this->phpfunc->method('ldap_connect')->willReturn(true);
        $this->phpfunc->method('ldap_bind')->willReturn(true);
        $this->phpfunc->method('ldap_search')->willReturn('result-resource');
        $this->phpfunc->method('ldap_get_entries')
            ->willReturn(array('count' => 0));

        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $this->newSearchAdapter()->login(array(
            'username' => 'nobody',
            'password' => 'secretpassword'
        ));
    }

    public function testLogin_searchMultipleMatches()
    {
        $this->phpfunc->method('ldap_connect')->willReturn(true);
        $this->phpfunc->method('ldap_bind')->willReturn(true);
        $this->phpfunc->method('ldap_search')->willReturn('result-resource');
        $this->phpfunc->method('ldap_get_entries')
            ->willReturn(array('count' => 2));

        $this->expectException('Aura\Auth\Exception\MultipleMatches');
        $this->newSearchAdapter()->login(array(
            'username' => 'alice',
            'password' => 'secretpassword'
        ));
    }

    public function testLogin_searchRebindFailed()
    {
        $this->phpfunc->method('ldap_connect')->willReturn(true);
        // service bind succeeds, user rebind fails
        $this->phpfunc->method('ldap_bind')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->phpfunc->method('ldap_search')->willReturn('result-resource');
        $this->phpfunc->method('ldap_get_entries')
            ->willReturn($this->fakeEntries());
        $this->phpfunc->method('ldap_errno')->willReturn(49);
        $this->phpfunc->method('ldap_error')->willReturn('Invalid credentials');

        $this->expectException('Aura\Auth\Exception\BindFailed');
        $this->newSearchAdapter()->login(array(
            'username' => 'alice',
            'password' => 'wrongpassword'
        ));
    }
}

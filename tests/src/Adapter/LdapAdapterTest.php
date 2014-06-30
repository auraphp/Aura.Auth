<?php
namespace Aura\Auth\Adapter;

class LdapAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected function setUp()
    {
        $this->adapter = new LdapAdapter('ldap.example.com', '');
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Aura\Auth\Adapter\LdapAdapter',
            $this->adapter
        );
    }
}

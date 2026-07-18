<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;
use PHPUnit\Framework\Attributes\Group;

/**
 *
 * Integration test that exercises {@see LdapAdapter} against a *real* LDAP
 * server using the real Phpfunc seam (no mocks).
 *
 * It is opt-in: unless the `LDAP_TEST_URI` environment variable is set (and the
 * ldap extension is loaded) every test is skipped, so the normal test run and
 * offline development are unaffected. CI provides the server -- see the
 * `ldap-integration` job in .github/workflows/continuous-integration.yml and
 * the seed data in tests/integration/ldap/seed.ldif.
 *
 * @package Aura.Auth
 *
 */
#[Group('ldap')]
class LdapAdapterIntegrationTest extends \PHPUnit\Framework\TestCase
{
    protected $server;

    protected function setUp() : void
    {
        $this->server = getenv('LDAP_TEST_URI');

        if (! $this->server) {
            $this->markTestSkipped(
                'Set LDAP_TEST_URI to run the LDAP integration tests.'
            );
        }

        if (! extension_loaded('ldap')) {
            $this->markTestSkipped('The ldap extension is not loaded.');
        }
    }

    protected function newAdapter($dnformat)
    {
        return new LdapAdapter(
            new Phpfunc(),
            $this->server,
            $dnformat,
            array(
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS => 0,
            )
        );
    }

    public function testLogin_peopleOu()
    {
        $adapter = $this->newAdapter('uid=%s,ou=people,dc=example,dc=org');

        $actual = $adapter->login(array(
            'username' => 'alice',
            'password' => 'alicepassword',
        ));

        $this->assertSame(array('alice', array()), $actual);
    }

    public function testLogin_contractorsOu()
    {
        $adapter = $this->newAdapter('uid=%s,ou=contractors,dc=example,dc=org');

        $actual = $adapter->login(array(
            'username' => 'bob',
            'password' => 'bobpassword',
        ));

        $this->assertSame(array('bob', array()), $actual);
    }

    public function testLogin_wrongPassword()
    {
        $adapter = $this->newAdapter('uid=%s,ou=people,dc=example,dc=org');

        $this->expectException('Aura\Auth\Exception\BindFailed');
        $adapter->login(array(
            'username' => 'alice',
            'password' => 'wrongpassword',
        ));
    }

    public function testLogin_unknownUser()
    {
        $adapter = $this->newAdapter('uid=%s,ou=people,dc=example,dc=org');

        $this->expectException('Aura\Auth\Exception\BindFailed');
        $adapter->login(array(
            'username' => 'nobody',
            'password' => 'whatever',
        ));
    }
}

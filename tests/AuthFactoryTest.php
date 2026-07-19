<?php
namespace Aura\Auth;

use PDO;
use Aura\Auth\Session\Session;
use Aura\Auth\Session\Segment;
use Aura\Auth\Verifier\FakeVerifier;

class AuthFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $factory;

    protected function setUp() : void
    {
        $this->factory = new AuthFactory($_COOKIE);
    }

    public function testNewAuth()
    {
        $auth = $this->factory->newInstance(array());
        $this->assertInstanceOf('Aura\Auth\Auth', $auth);
    }

    public function testNewAuthWithSessionAndSegment()
    {
        $auth = $this->factory->newInstance(array(), new Session(array()), new Segment);
        $this->assertInstanceOf('Aura\Auth\Auth', $auth);
    }

    public function testNewPdoAdapter_passwordVerifier()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Cannot test this adapter with pdo_sqlite extension disabled.");
        }

        $pdo = new PDO('sqlite::memory:');
        $adapter = $this->factory->newPdoAdapter(
            $pdo,
            1,
            array('username', 'password'),
            'accounts'
        );
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\PasswordVerifier',$verifier);
    }

    public function testNewPdoAdapter_customVerifier()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Cannot test this adapter with pdo_sqlite extension disabled.");
        }

        $pdo = new PDO('sqlite::memory:');
        $adapter = $this->factory->newPdoAdapter(
            $pdo,
            new FakeVerifier,
            array('username', 'password'),
            'accounts'
        );
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\FakeVerifier', $verifier);
    }

    public function testNewHtpasswdAdapter()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $adapter = $this->factory->newHtpasswdAdapter($file);
        $this->assertInstanceOf('Aura\Auth\Adapter\HtpasswdAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\HtpasswdVerifier', $verifier);
    }

    public function testNewImapAdapter()
    {
        $adapter = $this->factory->newImapAdapter('imap.example.com', '{mbox}');
        $this->assertInstanceOf('Aura\Auth\Adapter\ImapAdapter', $adapter);
    }

    public function testNewLdapAdapter()
    {
        $adapter = $this->factory->newLdapAdapter('ldap.example.com', 'ou=Org');
        $this->assertInstanceOf('Aura\Auth\Adapter\LdapAdapter', $adapter);
    }


    public function testNewLoginService()
    {
        $service = $this->factory->newLoginService();
        $this->assertInstanceOf('Aura\Auth\Service\LoginService', $service);
    }

    public function testNewLogoutService()
    {
        $service = $this->factory->newLogoutService();
        $this->assertInstanceOf('Aura\Auth\Service\LogoutService', $service);
    }

    public function testNewResumeService()
    {
        $service = $this->factory->newResumeService();
        $this->assertInstanceOf('Aura\Auth\Service\ResumeService', $service);
    }

    public function testNewPdoThrottleStorage()
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Cannot test without the pdo_sqlite extension.");
        }
        $storage = $this->factory->newPdoThrottleStorage(new PDO('sqlite::memory:'));
        $this->assertInstanceOf('Aura\Auth\Throttle\PdoThrottleStorage', $storage);
    }

    public function testNewRedisThrottleStorage_wrapsRawClient()
    {
        $storage = $this->factory->newRedisThrottleStorage(
            new \Aura\Auth\Throttle\FakeRedis()
        );
        $this->assertInstanceOf('Aura\Auth\Throttle\RedisThrottleStorage', $storage);
    }

    public function testNewRedisThrottleStorage_acceptsClientInterface()
    {
        $storage = $this->factory->newRedisThrottleStorage(
            new \Aura\Auth\Throttle\FakeRedisClient()
        );
        $this->assertInstanceOf('Aura\Auth\Throttle\RedisThrottleStorage', $storage);
    }

    public function testNewThrottleService()
    {
        $service = $this->factory->newThrottleService(
            new \Aura\Auth\Throttle\FakeThrottleStorage()
        );
        $this->assertInstanceOf('Aura\Auth\Throttle\ThrottleService', $service);
    }

    public function testNewThrottleAdapter()
    {
        $throttle = $this->factory->newThrottleService(
            new \Aura\Auth\Throttle\FakeThrottleStorage()
        );
        $adapter = $this->factory->newThrottleAdapter(
            new \Aura\Auth\Adapter\FakeAdapter(),
            $throttle
        );
        $this->assertInstanceOf('Aura\Auth\Adapter\ThrottleAdapter', $adapter);
    }
}

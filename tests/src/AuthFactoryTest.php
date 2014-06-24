<?php
namespace Aura\Auth;

use PDO;
use Aura\Auth\Verifier\FakeVerifier;

class AuthFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected function setUp()
    {
        $this->factory = new AuthFactory;
    }

    public function testNewUser()
    {
        $user = $this->factory->newUser(array());
        $this->assertInstanceOf('Aura\Auth\User', $user);
    }

    public function testNewPdoAdapter_hashVerifier()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = $this->factory->newPdoAdapter(
            $pdo,
            'md5',
            array('username', 'password'),
            'accounts'
        );
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\HashVerifier', $verifier);
    }

    public function testNewPdoAdapter_passwordVerifier()
    {
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
}

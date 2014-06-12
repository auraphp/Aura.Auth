<?php
namespace Aura\Auth;

use PDO;

class AuthFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected function setUp()
    {
        $this->factory = new AuthFactory;
    }

    public function testNewPdoInstance_hashVerifier()
    {
        $pdo = new PDO('sqlite::memory:');
        $auth = $this->factory->newPdoInstance(
            $pdo,
            'md5',
            array('username', 'password'),
            'accounts'
        );

        $this->assertInstanceOf('Aura\Auth\Auth', $auth);

        $adapter = $auth->getAdapter();
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\HashVerifier',$verifier);
    }

    public function testNewPdoInstance_passwordVerifier()
    {
        $pdo = new PDO('sqlite::memory:');
        $auth = $this->factory->newPdoInstance(
            $pdo,
            1,
            array('username', 'password'),
            'accounts'
        );

        $this->assertInstanceOf('Aura\Auth\Auth', $auth);

        $adapter = $auth->getAdapter();
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\PasswordVerifier',$verifier);
    }

    public function testNewPdoInstance_customVerifier()
    {
        $pdo = new PDO('sqlite::memory:');
        $auth = $this->factory->newPdoInstance(
            $pdo,
            new Verifier\FakeVerifier,
            array('username', 'password'),
            'accounts'
        );

        $this->assertInstanceOf('Aura\Auth\Auth', $auth);

        $adapter = $auth->getAdapter();
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\FakeVerifier',$verifier);
    }

    public function testNewHtpasswdInstance()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $auth = $this->factory->newHtpasswdInstance($file);

        $this->assertInstanceOf('Aura\Auth\Auth', $auth);

        $adapter = $auth->getAdapter();
        $this->assertInstanceOf('Aura\Auth\Adapter\HtpasswdAdapter', $adapter);
    }
}

<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\HtpasswdVerifier;
use Aura\Auth\Verifier\SpyVerifier;

class HtpasswdAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $adapter;

    protected function setUp() : void
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $this->setAdapter($file);
    }

    protected function setAdapter($file)
    {
        $this->adapter = new HtpasswdAdapter($file, new HtpasswdVerifier);
    }

    public function testLogin_fileNotReadable()
    {
        $this->setAdapter('no-such-file');
        $this->expectException('Aura\Auth\Exception\FileNotReadable');
        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
    }

    public function testLogin_success()
    {
        list($name, $data) = $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertSame('boshag', $name);
        $this->assertSame(array(), $data);
    }

    public function testLogin_usernameMissing()
    {
        $this->expectException('Aura\Auth\Exception\UsernameMissing');
        $this->adapter->login(array());
    }

    public function testLogin_passwordMissing()
    {
        $this->expectException('Aura\Auth\Exception\PasswordMissing');
        $this->adapter->login(array(
            'username' => 'boshag',
        ));
    }

    public function testLogin_usernameNotFound()
    {
        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login(array(
            'username' => 'nouser',
            'password' => 'nopass',
        ));
    }

    public function testLogin_usernameNotFound_verifiesDummyHash()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $spy = new SpyVerifier(new HtpasswdVerifier);
        $this->adapter = new HtpasswdAdapter($file, $spy);

        try {
            $this->adapter->login(array(
                'username' => 'nouser',
                'password' => 'nopass',
            ));
            $this->fail('Expected UsernameNotFound.');
        } catch (\Aura\Auth\Exception\UsernameNotFound $e) {
            // expected
        }

        $this->assertCount(1, $spy->calls);
        $this->assertSame('nopass', $spy->calls[0][0]);
        $this->assertContains($spy->calls[0][1], array(
            AbstractAdapter::DUMMY_HASH_COST_10,
            AbstractAdapter::DUMMY_HASH_COST_12,
        ));
    }

    /**
     * The dummy verification exists only to burn time; its result must never
     * be able to log anyone in.
     */
    public function testLogin_usernameNotFound_dummyCannotAuthenticate()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $this->adapter = new HtpasswdAdapter($file, new SpyVerifier(null, true));

        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login(array(
            'username' => 'nouser',
            'password' => 'anything at all',
        ));
    }

    public function testLogin_passwordIncorrect()
    {
        $this->expectException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '------',
        ));
    }
}

<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\HashVerifier;

class IniAdapterTest extends AbstractAdapterTest
{
    protected function setUp()
    {
        parent::setUp();

        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.inipasswd';
        $this->setAdapter($file);
    }

    protected function setAdapter($file)
    {
        $this->adapter = new IniAdapter($file, new HashVerifier('md5', 'hekkol233'));
    }

    public function testLogin_fileNotReadable()
    {
        $this->setAdapter('no-such-file');
        $this->setExpectedException('Aura\Auth\Exception\FileNotReadable');
        $this->adapter->login($this->user, array(
            'username' => 'pmjones',
            'password' => '123456',
        ));
    }

    public function testLogin_success()
    {
        $actual = $this->adapter->login($this->user, array(
            'username' => 'pmjones',
            'password' => '123456',
        ));
        $this->assertSame('pmjones', $this->user->getName());
    }

    public function testLogin_usernameMissing()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameMissing');
        $this->adapter->login($this->user, array());
    }

    public function testLogin_passwordMissing()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordMissing');
        $this->adapter->login($this->user, array(
            'username' => 'boshag',
        ));
    }

    public function testLogin_usernameNotFound()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login($this->user, array(
            'username' => 'nouser',
            'password' => 'nopass',
        ));
    }

    public function testLogin_passwordIncorrect()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login($this->user, array(
            'username' => 'harikt',
            'password' => '------',
        ));
    }
}

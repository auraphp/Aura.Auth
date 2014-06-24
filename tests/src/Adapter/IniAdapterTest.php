<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\HashVerifier;

class IniAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
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
        $this->adapter->login(array(
            'username' => 'pmjones',
            'password' => '123456',
        ));
    }

    public function testLogin_success()
    {
        list($name, $data) = $this->adapter->login(array(
            'username' => 'pmjones',
            'password' => '123456',
        ));
        $this->assertSame('pmjones', $name);
    }

    public function testLogin_usernameMissing()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameMissing');
        $this->adapter->login(array());
    }

    public function testLogin_passwordMissing()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordMissing');
        $this->adapter->login(array(
            'username' => 'boshag',
        ));
    }

    public function testLogin_usernameNotFound()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login(array(
            'username' => 'nouser',
            'password' => 'nopass',
        ));
    }

    public function testLogin_passwordIncorrect()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login(array(
            'username' => 'harikt',
            'password' => '------',
        ));
    }
}

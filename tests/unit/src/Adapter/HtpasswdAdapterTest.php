<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\HtpasswdVerifier;

class HtpasswdAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected function setUp()
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
        $this->setExpectedException('Aura\Auth\Exception\FileNotReadable');
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
            'username' => 'boshag',
            'password' => '------',
        ));
    }

    public function testUsingServerVarsForLogin() {
        $serverVars = array(
            'PHP_AUTH_USER' => 'brandon',
            'PHP_AUTH_PW' => 'password',
        );

        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $adapter = new HtpasswdAdapter($file, new HtpasswdVerifier(), $serverVars);

        list($name, $data) = $adapter->login();

        $this->assertSame('brandon', $name);
        $this->assertSame(array(), $data);
    }
}
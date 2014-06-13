<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\HtpasswdVerifier;

class HtpasswdAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $this->setAdapter($file);
    }

    protected function setAdapter($file)
    {
        $this->adapter = new HtpasswdAdapter($file, new HtpasswdVerifier);
    }

    public function testfileNotFound()
    {
        $this->setExpectedException('Aura\Auth\Exception');
        $this->setAdapter('no-such-file');
    }

    public function testLogin()
    {
        $this->assertTrue($this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        )));
    }

    public function testLogin_empty()
    {
        $this->assertFalse($this->adapter->login(array(
        )));
        $this->assertSame('Username empty.', $this->adapter->getError());

        $this->assertFalse($this->adapter->login(array(
            'username' => 'boshag',
        )));
        $this->assertSame('Password empty.', $this->adapter->getError());
    }

    public function testLogin_failed()
    {
        $this->assertFalse($this->adapter->login(array(
            'username' => 'nouser',
            'password' => 'nopass',
        )));

        $this->assertSame('Credentials failed.', $this->adapter->getError());
    }

    public function testLogin_incorrect()
    {
        $this->assertFalse($this->adapter->login(array(
            'username' => 'boshag',
            'password' => '------',
        )));

        $this->assertSame('Incorrect password.', $this->adapter->getError());
    }
}
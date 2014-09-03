<?php
namespace Aura\Auth\_Config;

use Aura\Di\ContainerAssertionsTrait;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAssertionsTrait;

    public function setUp()
    {
        $this->setUpContainer(array(
            'Aura\Auth\_Config\Common',
        ));
    }

    public function test()
    {
        $this->assertGet('aura/auth:auth', 'Aura\Auth\Auth');
        $this->assertGet('aura/auth:login_service', 'Aura\Auth\Service\LoginService');
        $this->assertGet('aura/auth:logout_service', 'Aura\Auth\Service\LogoutService');
        $this->assertGet('aura/auth:resume_service', 'Aura\Auth\Service\ResumeService');
        $this->assertGet('aura/auth:session', 'Aura\Auth\Session\Session');
        $this->assertGet('aura/auth:adapter', 'Aura\Auth\Adapter\NullAdapter');

        $this->assertNewInstance('Aura\Auth\Adapter\HtpasswdAdapter');
        $this->assertNewInstance('Aura\Auth\Adapter\ImapAdapter');
        $this->assertNewInstance('Aura\Auth\Adapter\LdapAdapter');
        $this->assertNewInstance('Aura\Auth\Adapter\PdoAdapter', array(
            'pdo' => new FakePDO
        ));
        $this->assertNewInstance('Aura\Auth\Auth');
        $this->assertNewInstance('Aura\Auth\Service\LoginService');
        $this->assertNewInstance('Aura\Auth\Service\LogoutService');
        $this->assertNewInstance('Aura\Auth\Service\ResumeService');
        $this->assertNewInstance('Aura\Auth\Session\Timer');
        $this->assertNewInstance('Aura\Auth\Session\Session');
    }
}

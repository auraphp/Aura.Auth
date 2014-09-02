<?php
namespace Aura\Auth\_Config;

use Aura\Di\ContainerBuilder;

class CommonTest extends \PHPUnit_Framework_TestCase
{
    protected $di;

    public function setUp()
    {
        $builder = new ContainerBuilder;
        $services = array();
        $configs = array('Aura\Auth\_Config\Common');
        $this->di = $builder->newInstance($services, $configs);
    }

    protected function assertService($name, $class)
    {
        $this->assertInstanceOf(
            $class,
            $this->di->get($name)
        );
    }

    protected function assertNewInstance(
        $class,
        $params = array(),
        $setter = array()
    ) {
        $this->assertInstanceOf(
            $class,
            $this->di->newInstance($class, $params, $setter)
        );
    }

    public function test()
    {
        $this->assertService('aura/auth:auth', 'Aura\Auth\Auth');
        $this->assertService('aura/auth:login_service', 'Aura\Auth\Service\LoginService');
        $this->assertService('aura/auth:logout_service', 'Aura\Auth\Service\LogoutService');
        $this->assertService('aura/auth:resume_service', 'Aura\Auth\Service\ResumeService');
        $this->assertService('aura/auth:session', 'Aura\Auth\Session\Session');
        $this->assertService('aura/auth:adapter', 'Aura\Auth\Adapter\NullAdapter');

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

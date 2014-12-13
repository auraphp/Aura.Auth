<?php
namespace Aura\Auth;

use PDO;
use Aura\Auth\Verifier\PasswordVerifier;

class AuthFacadeTest extends \PHPUnit_Framework_TestCase
{
    private $pdo;

    private $auth_facade;

    protected function setUp()
    {
        $auth_factory = new AuthFactory($_COOKIE);
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->buildTable();
        $adapter = $auth_factory->newPdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts',
            null
        );
        $this->auth_facade = new AuthFacade($auth_factory, $adapter);
    }

    protected function buildTable()
    {
        $stm = "CREATE TABLE accounts (
            username VARCHAR(255),
            password VARCHAR(255),
            active VARCHAR(255)
        )";

        $this->pdo->query($stm);

        $rows = array(
            array(
                'username' => 'boshag',
                'password' => hash('md5', '123456'),
                'active'    => 'y',
            ),
            array(
                'username' => 'repeat',
                'password' => hash('md5', '234567'),
                'active'    => 'y',
            ),
            array(
                'username' => 'repeat',
                'password' => hash('md5', '234567'),
                'active'    => 'n',
            ),
        );

        $stm = "INSERT INTO accounts (username, password, active)
                VALUES (:username, :password, :active)";

        $sth = $this->pdo->prepare($stm);

        foreach ($rows as $row) {
            $sth->execute($row);
        }
    }

    public function testLogin()
    {
        $this->assertSame('ANON', $this->auth_facade->getStatus());
        $this->auth_facade->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('VALID', $this->auth_facade->getStatus());
    }

    public function testLogout()
    {
        $this->auth_facade->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('VALID', $this->auth_facade->getStatus());
        $this->auth_facade->logout();
        $this->assertSame('ANON', $this->auth_facade->getStatus());
    }

    public function testForceLogout()
    {
        $this->auth_facade->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('VALID', $this->auth_facade->getStatus());
        $this->auth_facade->forceLogout();
        $this->assertSame('ANON', $this->auth_facade->getStatus());
    }

    public function testForceLogin($username, $userdata)
    {
        $this->assertSame('ANON', $this->auth_facade->getStatus());
        $this->auth_facade->forceLogin(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('VALID', $this->auth_facade->getStatus());
    }
}

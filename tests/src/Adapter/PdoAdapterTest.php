<?php
namespace Aura\Auth\Adapter;

use PDO;
use Aura\Auth\Verifier\HashVerifier;

class PdoAdapterTest extends AbstractAdapterTest
{
    protected $pdo;

    protected function setUp()
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->buildTable();
        $this->setAdapter();
    }

    protected function setAdapter($where = null)
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new HashVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts',
            $where
        );
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

    public function test_usernameColumnNotSpecified()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameColumnNotSpecified');
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new HashVerifier('md5'),
            array(),
            'accounts'
        );
    }

    public function test_passwordColumnNotSpecified()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordColumnNotSpecified');
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new HashVerifier('md5'),
            array('username'),
            'accounts'
        );
    }

    public function testLogin()
    {
        $this->adapter->login($this->user, array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('boshag', $this->user->getName());
        $this->assertSame(array('active' => 'y'), $this->user->getData());
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
            'username' => 'missing',
            'password' => '------',
        ));
    }

    public function testLogin_passwordIncorrect()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login($this->user, array(
            'username' => 'boshag',
            'password' => '------',
        ));
    }

    public function testLogin_multipleMatches()
    {
        $this->setExpectedException('Aura\Auth\Exception\MultipleMatches');
        $this->adapter->login($this->user, array(
            'username' => 'repeat',
            'password' => '234567',
        ));
    }

    public function testLogin_where()
    {
        $this->setAdapter("active = :active");
        $this->adapter->login($this->user, array(
            'username' => 'repeat',
            'password' => '234567',
            'active' => 'y',
        ));
        $this->assertSame('repeat', $this->user->getName());
        $this->assertSame(array('active' => 'y'), $this->user->getData());
    }
}

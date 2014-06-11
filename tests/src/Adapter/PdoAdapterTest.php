<?php
namespace Aura\Auth\Adapter;

use PDO;
use Aura\Auth\PasswordVerifier;

class PdoAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $pdo;

    protected function setUp()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->buildTable();
        $this->setAdapter();
    }

    protected function setAdapter($where = null)
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('hash', 'md5'),
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

    public function testLogin()
    {
        $this->assertTrue($this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        )));

        $this->assertSame('boshag', $this->adapter->getUser());
        $this->assertSame(array('active' => 'y'), $this->adapter->getInfo());
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
            'username' => 'missing',
            'password' => '------',
        )));

        $this->assertSame('Credentials failed.', $this->adapter->getError());
    }

    public function testLogin_incorrect()
    {
        $this->assertFalse($this->adapter->login(array(
            'username' => 'boshag',
            'password' => '------',
        )));

        $this->assertSame('Password incorrect.', $this->adapter->getError());
    }

    public function testLogin_duplicates()
    {
        $this->assertFalse($this->adapter->login(array(
            'username' => 'repeat',
            'password' => '234567'
        )));

        $this->assertSame('Duplicate credentials.', $this->adapter->getError());
    }

    public function testLogin_where()
    {
        $this->setAdapter("active = :active");
        $this->assertTrue($this->adapter->login(array(
            'username' => 'repeat',
            'password' => '234567',
            'active' => 'y',
        )));
    }
}

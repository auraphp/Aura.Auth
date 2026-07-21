<?php
namespace Aura\Auth\Rehash;

use PDO;

class PdoRehashStorageTest extends \PHPUnit\Framework\TestCase
{
    protected $pdo;

    protected function setUp() : void
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Cannot test this storage with pdo_sqlite extension disabled.");
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->query(
            "CREATE TABLE accounts (username VARCHAR(255), password VARCHAR(255))"
        );
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password) VALUES (:u, :p)"
        );
        $sth->execute(array('u' => 'alice', 'p' => hash('md5', 'secret')));
        $sth->execute(array('u' => 'bob', 'p' => hash('md5', 'other')));
    }

    protected function fetchHash($username)
    {
        $sth = $this->pdo->prepare(
            "SELECT password FROM accounts WHERE username = :u"
        );
        $sth->execute(array('u' => $username));
        return $sth->fetchColumn();
    }

    public function testRehashReplacesTheStoredHash()
    {
        $storage = new PdoRehashStorage(
            $this->pdo,
            'accounts',
            'username',
            'password',
            PASSWORD_BCRYPT,
            array('cost' => 4)
        );

        $storage->rehash('alice', 'secret');

        $stored = $this->fetchHash('alice');
        $this->assertSame(PASSWORD_BCRYPT, password_get_info($stored)['algo']);
        $this->assertTrue(password_verify('secret', $stored));
    }

    public function testRehashLeavesOtherAccountsAlone()
    {
        $before = $this->fetchHash('bob');

        $storage = new PdoRehashStorage(
            $this->pdo,
            'accounts',
            'username',
            'password',
            PASSWORD_BCRYPT,
            array('cost' => 4)
        );
        $storage->rehash('alice', 'secret');

        $this->assertSame($before, $this->fetchHash('bob'));
    }

    public function testRehashHonoursCustomColumnNames()
    {
        $this->pdo->query(
            "CREATE TABLE members (login VARCHAR(255), pwhash VARCHAR(255))"
        );
        $sth = $this->pdo->prepare(
            "INSERT INTO members (login, pwhash) VALUES (:u, :p)"
        );
        $sth->execute(array('u' => 'carol', 'p' => hash('md5', 'third')));

        $storage = new PdoRehashStorage(
            $this->pdo,
            'members',
            'login',
            'pwhash',
            PASSWORD_BCRYPT,
            array('cost' => 4)
        );
        $storage->rehash('carol', 'third');

        $sth = $this->pdo->prepare("SELECT pwhash FROM members WHERE login = :u");
        $sth->execute(array('u' => 'carol'));
        $this->assertTrue(password_verify('third', $sth->fetchColumn()));
    }
}

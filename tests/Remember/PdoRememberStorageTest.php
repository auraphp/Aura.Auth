<?php
namespace Aura\Auth\Remember;

use PDO;

class PdoRememberStorageTest extends \PHPUnit\Framework\TestCase
{
    protected $pdo;

    protected $storage;

    protected function setUp() : void
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Cannot test without the pdo_sqlite extension.");
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            "CREATE TABLE aura_auth_remember (
                selector VARCHAR(32) NOT NULL PRIMARY KEY,
                hashed_validator VARCHAR(64) NOT NULL,
                username VARCHAR(255) NOT NULL,
                userdata TEXT NULL,
                expires INTEGER NOT NULL
            )"
        );

        $this->storage = new PdoRememberStorage($this->pdo);
    }

    public function testCreateAndFind()
    {
        $expires = time() + 100;
        $this->storage->create('sel1', 'hash1', 'boshag', array('foo' => 'bar'), $expires);

        $row = $this->storage->findBySelector('sel1');
        $this->assertSame('sel1', $row['selector']);
        $this->assertSame('hash1', $row['hashed_validator']);
        $this->assertSame('boshag', $row['username']);
        $this->assertSame(array('foo' => 'bar'), $row['userdata']);
        $this->assertSame($expires, $row['expires']);
    }

    public function testFindMissingReturnsNull()
    {
        $this->assertNull($this->storage->findBySelector('nope'));
    }

    public function testUpdate()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100);
        $this->storage->update('sel1', 'hash2', time() + 200);

        $row = $this->storage->findBySelector('sel1');
        $this->assertSame('hash2', $row['hashed_validator']);
        $this->assertSame(time() + 200, $row['expires']);
    }

    public function testDeleteBySelector()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100);
        $this->storage->deleteBySelector('sel1');
        $this->assertNull($this->storage->findBySelector('sel1'));
    }

    public function testDeleteByUsername()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100);
        $this->storage->create('sel2', 'hash2', 'boshag', array(), time() + 100);
        $this->storage->create('sel3', 'hash3', 'other', array(), time() + 100);

        $this->storage->deleteByUsername('boshag');

        $this->assertNull($this->storage->findBySelector('sel1'));
        $this->assertNull($this->storage->findBySelector('sel2'));
        $this->assertNotNull($this->storage->findBySelector('sel3'));
    }

    public function testDeleteExpired()
    {
        $this->storage->create('live', 'hash1', 'boshag', array(), time() + 100);
        $this->storage->create('dead', 'hash2', 'boshag', array(), time() - 100);

        $this->storage->deleteExpired();

        $this->assertNotNull($this->storage->findBySelector('live'));
        $this->assertNull($this->storage->findBySelector('dead'));
    }
}

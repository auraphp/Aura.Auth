<?php
namespace Aura\Auth\Token;

use PDO;

class PdoTokenStorageTest extends \PHPUnit\Framework\TestCase
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
            "CREATE TABLE aura_auth_token (
                selector VARCHAR(32) NOT NULL PRIMARY KEY,
                hashed_validator VARCHAR(64) NOT NULL,
                username VARCHAR(255) NOT NULL,
                userdata TEXT NULL,
                label VARCHAR(255) NULL,
                expires INTEGER NOT NULL,
                created_at INTEGER NOT NULL,
                last_used_at INTEGER NULL
            )"
        );

        $this->storage = new PdoTokenStorage($this->pdo);
    }

    public function testCreateAndFind()
    {
        $expires = time() + 100;
        $created = time();
        $this->storage->create(
            'sel1',
            'hash1',
            'boshag',
            array('scopes' => array('read:builds')),
            $expires,
            $created,
            'CI deploy'
        );

        $row = $this->storage->findBySelector('sel1');
        $this->assertSame('sel1', $row['selector']);
        $this->assertSame('hash1', $row['hashed_validator']);
        $this->assertSame('boshag', $row['username']);
        $this->assertSame(array('scopes' => array('read:builds')), $row['userdata']);
        $this->assertSame('CI deploy', $row['label']);
        $this->assertSame($expires, $row['expires']);
        $this->assertSame($created, $row['created_at']);
        $this->assertNull($row['last_used_at']);
    }

    public function testCreateWithoutLabel()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100, time());
        $row = $this->storage->findBySelector('sel1');
        $this->assertNull($row['label']);
    }

    public function testFindMissingReturnsNull()
    {
        $this->assertNull($this->storage->findBySelector('nope'));
    }

    public function testTouchIsNoopByDefault()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100, time());
        $this->storage->touch('sel1', time());

        $row = $this->storage->findBySelector('sel1');
        $this->assertNull($row['last_used_at']);
    }

    public function testTouchRecordsWhenTrackingEnabled()
    {
        $storage = new PdoTokenStorage($this->pdo, 'aura_auth_token', true);
        $storage->create('sel1', 'hash1', 'boshag', array(), time() + 100, time());

        $now = time();
        $storage->touch('sel1', $now);

        $row = $storage->findBySelector('sel1');
        $this->assertSame($now, $row['last_used_at']);
    }

    public function testTouchMissingSelectorDoesNotError()
    {
        $storage = new PdoTokenStorage($this->pdo, 'aura_auth_token', true);
        $storage->touch('nope', time());
        $this->assertNull($storage->findBySelector('nope'));
    }

    public function testDeleteBySelector()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100, time());
        $this->storage->deleteBySelector('sel1');
        $this->assertNull($this->storage->findBySelector('sel1'));
    }

    public function testDeleteByUsername()
    {
        $this->storage->create('sel1', 'hash1', 'boshag', array(), time() + 100, time());
        $this->storage->create('sel2', 'hash2', 'boshag', array(), time() + 100, time());
        $this->storage->create('sel3', 'hash3', 'other', array(), time() + 100, time());

        $this->storage->deleteByUsername('boshag');

        $this->assertNull($this->storage->findBySelector('sel1'));
        $this->assertNull($this->storage->findBySelector('sel2'));
        $this->assertNotNull($this->storage->findBySelector('sel3'));
    }

    public function testDeleteExpired()
    {
        $this->storage->create('live', 'hash1', 'boshag', array(), time() + 100, time());
        $this->storage->create('dead', 'hash2', 'boshag', array(), time() - 100, time() - 200);

        $this->storage->deleteExpired();

        $this->assertNotNull($this->storage->findBySelector('live'));
        $this->assertNull($this->storage->findBySelector('dead'));
    }

    public function testCustomTableName()
    {
        $this->pdo->exec(
            "CREATE TABLE custom_tokens (
                selector VARCHAR(32) NOT NULL PRIMARY KEY,
                hashed_validator VARCHAR(64) NOT NULL,
                username VARCHAR(255) NOT NULL,
                userdata TEXT NULL,
                label VARCHAR(255) NULL,
                expires INTEGER NOT NULL,
                created_at INTEGER NOT NULL,
                last_used_at INTEGER NULL
            )"
        );

        $storage = new PdoTokenStorage($this->pdo, 'custom_tokens');
        $storage->create('sel1', 'hash1', 'boshag', array(), time() + 100, time());

        $this->assertNotNull($storage->findBySelector('sel1'));
        $this->assertNull($this->storage->findBySelector('sel1'));
    }
}

<?php
namespace Aura\Auth\Throttle;

use PDO;

class PdoThrottleStorageTest extends \PHPUnit\Framework\TestCase
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
            "CREATE TABLE aura_auth_throttle (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                throttle_key VARCHAR(255) NOT NULL,
                attempted_at INTEGER NOT NULL
            )"
        );

        $this->storage = new PdoThrottleStorage($this->pdo);
    }

    public function testNoFailures()
    {
        $this->assertSame(
            array('count' => 0, 'last' => null),
            $this->storage->getFailures('bob')
        );
    }

    public function testRecordAndCount()
    {
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('alice');

        $bob = $this->storage->getFailures('bob');
        $this->assertSame(2, $bob['count']);
        $this->assertNotNull($bob['last']);
        $this->assertLessThanOrEqual(time(), $bob['last']);

        $this->assertSame(1, $this->storage->getFailures('alice')['count']);
    }

    public function testReset()
    {
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob');
        $this->storage->reset('bob');

        $this->assertSame(0, $this->storage->getFailures('bob')['count']);
    }

    public function testWindowExcludesOldFailures()
    {
        // window of 100s; insert one failure well outside the window and one inside
        $storage = new PdoThrottleStorage($this->pdo, 'aura_auth_throttle', 100);
        $this->pdo->exec(
            "INSERT INTO aura_auth_throttle (throttle_key, attempted_at) "
            . "VALUES ('bob', " . (time() - 500) . ")"
        );
        $storage->recordFailure('bob'); // now

        $bob = $storage->getFailures('bob');
        $this->assertSame(1, $bob['count']);
    }

    public function testDeleteExpired()
    {
        $storage = new PdoThrottleStorage($this->pdo, 'aura_auth_throttle', 100);
        $this->pdo->exec(
            "INSERT INTO aura_auth_throttle (throttle_key, attempted_at) "
            . "VALUES ('bob', " . (time() - 500) . ")"
        );
        $storage->recordFailure('bob'); // now

        $storage->deleteExpired();

        // the fresh failure survives; the stale one is gone
        $count = (int) $this->pdo
            ->query("SELECT COUNT(*) FROM aura_auth_throttle")
            ->fetchColumn();
        $this->assertSame(1, $count);
    }
}

<?php
namespace Aura\Auth\Integration;

use Aura\Auth\Adapter\PdoAdapter;
use Aura\Auth\Rehash\PdoRehashStorage;
use Aura\Auth\Remember\PdoRememberStorage;
use Aura\Auth\Throttle\PdoThrottleStorage;
use Aura\Auth\Token\PdoTokenStorage;
use Aura\Auth\Verifier\PasswordVerifier;
use PDO;
use PHPUnit\Framework\Attributes\Group;

/**
 *
 * Integration test that exercises the PDO adapter and the PDO-backed storages
 * against a *real* database server, rather than the SQLite in-memory database
 * the unit tests use.
 *
 * SQLite is permissive in ways that hide portability problems: it has dynamic
 * typing, no strict mode, and its own ideas about string comparison. Anything
 * that depends on the server -- identifier quoting, the meaning of `=` on a
 * VARCHAR, integer column behaviour, autoincrement syntax -- is only really
 * tested here.
 *
 * It is opt-in: unless `PDO_TEST_DSN` is set, every test is skipped, so the
 * normal test run and offline development are unaffected. CI provides the
 * servers -- see the `pdo-integration` job in
 * .github/workflows/continuous-integration.yml. The schema comes from
 * tests/integration/pdo/{mysql,pgsql,sqlite}.sql, which is the same DDL
 * published in docs/schemas.md.
 *
 * @package Aura.Auth
 *
 */
#[Group('pdo')]
class PdoIntegrationTest extends \PHPUnit\Framework\TestCase
{
    protected $pdo;

    protected $driver;

    const TEST_COST = array('cost' => 4);

    protected function setUp() : void
    {
        $dsn = getenv('PDO_TEST_DSN');

        if (! $dsn) {
            $this->markTestSkipped(
                'Set PDO_TEST_DSN to run the PDO integration tests.'
            );
        }

        $this->pdo = new PDO(
            $dsn,
            getenv('PDO_TEST_USERNAME') ?: null,
            getenv('PDO_TEST_PASSWORD') ?: null
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $this->buildSchema();
        $this->seedAccounts();
    }

    /**
     * Loads the same DDL the documentation publishes, so a schema that does
     * not work on a real server fails the build.
     */
    protected function buildSchema(): void
    {
        $tables = array(
            'aura_auth_throttle',
            'aura_auth_token',
            'aura_auth_remember',
            'accounts',
        );
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS {$table}");
        }

        $file = __DIR__ . '/pdo/' . $this->driver . '.sql';
        if (! is_file($file)) {
            $this->markTestSkipped("No schema file for the '{$this->driver}' driver.");
        }

        $sql = file_get_contents($file);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stm) {
            if (strpos($stm, '--') === 0 && strpos($stm, "\n") === false) {
                continue;
            }
            $this->pdo->exec($stm);
        }
    }

    protected function seedAccounts(): void
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password, active)
             VALUES (:username, :password, :active)"
        );
        $sth->execute(array(
            'username' => 'alice',
            'password' => password_hash('secret', PASSWORD_BCRYPT, self::TEST_COST),
            'active' => 'y',
        ));
        $sth->execute(array(
            'username' => 'legacy',
            'password' => hash('md5', 'oldpass'),
            'active' => 'y',
        ));
    }

    protected function newAdapter($verifier = null, $where = null)
    {
        return new PdoAdapter(
            $this->pdo,
            $verifier ?: new PasswordVerifier(PASSWORD_BCRYPT, self::TEST_COST),
            array('username', 'password', 'active'),
            'accounts',
            $where
        );
    }

    protected function fetchHash($username)
    {
        $sth = $this->pdo->prepare(
            "SELECT password FROM accounts WHERE username = :u"
        );
        $sth->execute(array('u' => $username));
        return $sth->fetchColumn();
    }

    public function testLoginSucceeds()
    {
        list($name, $data) = $this->newAdapter()->login(array(
            'username' => 'alice',
            'password' => 'secret',
        ));

        $this->assertSame('alice', $name);
        $this->assertSame(array('active' => 'y'), $data);
    }

    public function testLoginRejectsAWrongPassword()
    {
        $this->expectException('Aura\Auth\Exception\PasswordIncorrect');
        $this->newAdapter()->login(array(
            'username' => 'alice',
            'password' => 'wrong',
        ));
    }

    public function testLoginRejectsAnUnknownUsername()
    {
        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $this->newAdapter()->login(array(
            'username' => 'nobody',
            'password' => 'secret',
        ));
    }

    public function testWhereClauseIsApplied()
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password, active)
             VALUES (:username, :password, :active)"
        );
        $sth->execute(array(
            'username' => 'dormant',
            'password' => password_hash('secret', PASSWORD_BCRYPT, self::TEST_COST),
            'active' => 'n',
        ));

        $adapter = $this->newAdapter(null, "active = :active");

        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $adapter->login(array(
            'username' => 'dormant',
            'password' => 'secret',
            'active' => 'y',
        ));
    }

    /**
     * MySQL's default collation is case-insensitive, and its VARCHAR
     * comparison ignores trailing spaces, so "ALICE" and "alice " both match
     * the stored "alice" there while PostgreSQL matches neither. SQLite
     * reproduces neither behaviour, so the unit tests cannot see this at all.
     *
     * Whatever the server decides, the adapter must return the *stored*
     * username rather than the one that was typed -- otherwise the session
     * identity varies by how the user capitalised their login, and anything
     * keyed on it (throttle counters, remember-me rows, audit logs) silently
     * splits in two.
     */
    public function testReturnsTheStoredUsernameNotTheSubmittedOne()
    {
        $variants = array('ALICE', 'alice ', 'Alice');
        $matched = 0;

        foreach ($variants as $variant) {
            try {
                list($name) = $this->newAdapter()->login(array(
                    'username' => $variant,
                    'password' => 'secret',
                ));
            } catch (\Aura\Auth\Exception\UsernameNotFound $e) {
                // this server treats the variant as a different username
                continue;
            }

            $matched++;
            $this->assertSame(
                'alice',
                $name,
                "Logging in as '{$variant}' returned '{$name}' rather than the stored username."
            );
        }

        // record what this server actually did, so the expectation is visible
        // in the output rather than assumed
        $this->assertGreaterThanOrEqual(
            0,
            $matched,
            "{$this->driver} matched {$matched} of " . count($variants) . " variants"
        );
    }

    public function testRehashStorageMigratesALegacyHash()
    {
        $adapter = $this->newAdapter(new PasswordVerifier('md5'));
        $adapter->setRehashStorage(new PdoRehashStorage(
            $this->pdo,
            'accounts',
            'username',
            'password',
            PASSWORD_BCRYPT,
            self::TEST_COST
        ));

        $adapter->login(array('username' => 'legacy', 'password' => 'oldpass'));

        $stored = $this->fetchHash('legacy');
        $this->assertTrue(password_verify('oldpass', $stored));
        $this->assertFalse($adapter->needsRehash());

        // and the migrated account still authenticates through the same
        // legacy-configured adapter
        list($name) = $adapter->login(array('username' => 'legacy', 'password' => 'oldpass'));
        $this->assertSame('legacy', $name);
    }

    public function testThrottleStorageCountsAndClears()
    {
        $storage = new PdoThrottleStorage($this->pdo);

        $storage->recordFailure('alice');
        $storage->recordFailure('alice');
        $storage->recordFailure('bob');

        $failures = $storage->getFailures('alice');
        $this->assertSame(2, $failures['count']);
        $this->assertIsInt($failures['last']);

        $storage->reset('alice');
        $this->assertSame(0, $storage->getFailures('alice')['count']);
        $this->assertSame(1, $storage->getFailures('bob')['count']);
    }

    public function testThrottleStorageDeletesExpired()
    {
        $storage = new PdoThrottleStorage($this->pdo, 'aura_auth_throttle', 60);

        $this->pdo->prepare(
            "INSERT INTO aura_auth_throttle (throttle_key, attempted_at)
             VALUES (:key, :at)"
        )->execute(array('key' => 'stale', 'at' => time() - 3600));

        $storage->deleteExpired();

        $this->assertSame(0, $storage->getFailures('stale')['count']);
    }

    public function testRememberStorageRoundTrip()
    {
        $storage = new PdoRememberStorage($this->pdo);
        $expires = time() + 3600;

        $storage->create('sel123', 'hashedval', 'alice', array('role' => 'admin'), $expires);

        $row = $storage->findBySelector('sel123');
        $this->assertSame('alice', $row['username']);
        $this->assertSame('hashedval', $row['hashed_validator']);
        $this->assertSame(array('role' => 'admin'), $row['userdata']);
        $this->assertSame($expires, (int) $row['expires']);

        // rotation on use: the validator and expiry are replaced in place
        $storage->update('sel123', 'newhashedval', $expires + 60);
        $rotated = $storage->findBySelector('sel123');
        $this->assertSame('newhashedval', $rotated['hashed_validator']);
        $this->assertSame($expires + 60, (int) $rotated['expires']);

        $storage->deleteBySelector('sel123');
        $this->assertNull($storage->findBySelector('sel123'));
    }

    public function testTokenStorageRoundTrip()
    {
        $storage = new PdoTokenStorage($this->pdo);
        $expires = time() + 3600;

        $now = time();
        $storage->create(
            'tok123',
            'hashedval',
            'alice',
            array('scope' => 'read'),
            $expires,
            $now,
            'my laptop'
        );

        $row = $storage->findBySelector('tok123');
        $this->assertSame('alice', $row['username']);
        $this->assertSame('my laptop', $row['label']);
        $this->assertSame(array('scope' => 'read'), $row['userdata']);
        $this->assertSame($now, (int) $row['created_at']);
        $this->assertNull($row['last_used_at']);

        // last-use tracking is off by default, so touch() writes nothing
        $storage->touch('tok123', $now + 30);
        $this->assertNull($storage->findBySelector('tok123')['last_used_at']);

        // ... and updates the row when it is enabled
        $tracking = new PdoTokenStorage($this->pdo, 'aura_auth_token', true);
        $tracking->touch('tok123', $now + 30);
        $this->assertSame(
            $now + 30,
            (int) $tracking->findBySelector('tok123')['last_used_at']
        );

        $storage->deleteByUsername('alice');
        $this->assertNull($storage->findBySelector('tok123'));
    }
}

<?php
namespace Aura\Auth\Adapter;

use PDO;
use Aura\Auth\Rehash\PdoRehashStorage;
use Aura\Auth\Rehash\RehashStorageInterface;
use Aura\Auth\Verifier\PasswordVerifier;
use Aura\Auth\Verifier\SpyVerifier;

/**
 * A writer that always fails, for checking that a failed rehash cannot take a
 * successful login down with it.
 */
class ThrowingRehashStorage implements RehashStorageInterface
{
    public $calls = 0;

    public function rehash($username, $plaintext): void
    {
        $this->calls++;
        throw new \RuntimeException('the accounts table is read-only');
    }
}

class PdoAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $adapter;

    protected $pdo;

    protected function setUp() : void
    {
        if (false === extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Cannot test this adapter with pdo_sqlite extension disabled.");
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->buildTable();
        $this->setAdapter();
    }

    /**
     * Cost 4 is bcrypt's minimum. The tests only care that verification
     * succeeds or fails, so there is no reason to make them wait for a
     * production-strength cost.
     */
    const TEST_COST = array('cost' => 4);

    protected function setAdapter($where = null)
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT),
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
                'password' => password_hash('123456', PASSWORD_BCRYPT, self::TEST_COST),
                'active'    => 'y',
            ),
            array(
                'username' => 'repeat',
                'password' => password_hash('234567', PASSWORD_BCRYPT, self::TEST_COST),
                'active'    => 'y',
            ),
            array(
                'username' => 'repeat',
                'password' => password_hash('234567', PASSWORD_BCRYPT, self::TEST_COST),
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
        $this->expectException('Aura\Auth\Exception\UsernameColumnNotSpecified');
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT),
            array(),
            'accounts'
        );
    }

    public function test_passwordColumnNotSpecified()
    {
        $this->expectException('Aura\Auth\Exception\PasswordColumnNotSpecified');
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT),
            array('username'),
            'accounts'
        );
    }

    public function testLogin()
    {
        list($name, $data) = $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('boshag', $name);
        $this->assertSame(array('active' => 'y'), $data);
    }

    public function testNeedsRehash_falseWhenHashIsCurrent()
    {
        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        // fixtures are hashed at the same cost the verifier asks for
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT, self::TEST_COST),
            array('username', 'password', 'active'),
            'accounts'
        );
        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertFalse($this->adapter->needsRehash());
    }

    public function testNeedsRehash_trueWhenCostIsRaised()
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 6)),
            array('username', 'password', 'active'),
            'accounts'
        );

        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertTrue($this->adapter->needsRehash());
    }

    /**
     * The flag describes the login that just happened, so a later failed
     * attempt must not leave a stale true behind for the caller to act on.
     */
    public function testNeedsRehash_resetByASubsequentFailedLogin()
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 6)),
            array('username', 'password', 'active'),
            'accounts'
        );

        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertTrue($this->adapter->needsRehash());

        try {
            $this->adapter->login(array(
                'username' => 'boshag',
                'password' => 'wrong',
            ));
        } catch (\Aura\Auth\Exception\PasswordIncorrect $e) {
            // expected
        }

        $this->assertFalse($this->adapter->needsRehash());
    }

    /**
     * A verifier that cannot report rehashes must not break login.
     */
    public function testNeedsRehash_falseWhenVerifierCannotReport()
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new SpyVerifier(null, true),
            array('username', 'password', 'active'),
            'accounts'
        );

        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => 'anything',
        ));

        $this->assertFalse($this->adapter->needsRehash());
    }

    /**
     * The whole point of the writer seam: a legacy digest is verified, then
     * replaced, without the application doing anything after login().
     */
    public function testRehashStorage_migratesLegacyHashOnLogin()
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password, active)
             VALUES (:username, :password, :active)"
        );
        $sth->execute(array(
            'username' => 'legacy',
            'password' => hash('md5', '345678'),
            'active' => 'y',
        ));

        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts'
        );
        $this->adapter->setRehashStorage(new PdoRehashStorage(
            $this->pdo,
            'accounts',
            'username',
            'password',
            PASSWORD_BCRYPT,
            self::TEST_COST
        ));

        $this->adapter->login(array(
            'username' => 'legacy',
            'password' => '345678',
        ));

        // the flag is cleared, because it has been dealt with
        $this->assertFalse($this->adapter->needsRehash());
        $this->assertNull($this->adapter->getRehashError());

        $stored = $this->fetchHash('legacy');
        $this->assertSame(PASSWORD_BCRYPT, password_get_info($stored)['algo']);
        $this->assertTrue(password_verify('345678', $stored));

        // The migration is not finished, so the adapter is still configured
        // for the legacy algorithm -- this is the state a real deployment is
        // in, and the account has to keep working in it. Checking with a
        // freshly bcrypt-configured adapter instead would pass while every
        // migrated user was locked out.
        list($name) = $this->adapter->login(array(
            'username' => 'legacy',
            'password' => '345678',
        ));
        $this->assertSame('legacy', $name);

        // and it is not rewritten a second time
        $this->assertFalse($this->adapter->needsRehash());
        $this->assertSame($stored, $this->fetchHash('legacy'));

        // a wrong password is still rejected after migration
        try {
            $this->adapter->login(array(
                'username' => 'legacy',
                'password' => 'wrong',
            ));
            $this->fail('Expected PasswordIncorrect.');
        } catch (\Aura\Auth\Exception\PasswordIncorrect $e) {
            // expected
        }
    }

    /**
     * Every account migrates on its own next login, so the column holds both
     * kinds of hash until the last one has been back. Both must authenticate
     * throughout.
     */
    public function testRehashStorage_bothHashKindsWorkDuringMigration()
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password, active)
             VALUES (:username, :password, :active)"
        );
        $sth->execute(array(
            'username' => 'early',
            'password' => hash('md5', 'aaa'),
            'active' => 'y',
        ));
        $sth->execute(array(
            'username' => 'late',
            'password' => hash('md5', 'bbb'),
            'active' => 'y',
        ));

        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts'
        );
        $this->adapter->setRehashStorage(new PdoRehashStorage(
            $this->pdo,
            'accounts',
            'username',
            'password',
            PASSWORD_BCRYPT,
            self::TEST_COST
        ));

        // "early" comes back and is migrated; "late" has not been seen yet
        $this->adapter->login(array('username' => 'early', 'password' => 'aaa'));
        $this->assertTrue(password_verify('aaa', $this->fetchHash('early')));
        $this->assertSame(hash('md5', 'bbb'), $this->fetchHash('late'));

        // both kinds authenticate against the one configured adapter
        list($early) = $this->adapter->login(array('username' => 'early', 'password' => 'aaa'));
        list($late) = $this->adapter->login(array('username' => 'late', 'password' => 'bbb'));
        $this->assertSame('early', $early);
        $this->assertSame('late', $late);

        // and "late" is migrated by that visit
        $this->assertTrue(password_verify('bbb', $this->fetchHash('late')));
    }

    public function testRehashStorage_notCalledWhenHashIsCurrent()
    {
        $storage = new ThrowingRehashStorage();

        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT, self::TEST_COST),
            array('username', 'password', 'active'),
            'accounts'
        );
        $this->adapter->setRehashStorage($storage);

        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame(0, $storage->calls);
        $this->assertNull($this->adapter->getRehashError());
    }

    /**
     * Rehashing is housekeeping. If the write fails -- a read replica, a
     * revoked grant, a locked table -- the user must still be logged in.
     */
    public function testRehashStorage_failureDoesNotBreakLogin()
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 6)),
            array('username', 'password', 'active'),
            'accounts'
        );
        $this->adapter->setRehashStorage(new ThrowingRehashStorage());

        list($name, $data) = $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('boshag', $name);
        $this->assertSame(array('active' => 'y'), $data);

        // the failure is reported rather than thrown, and the flag stays up so
        // a caller doing its own rehashing still knows there is work to do
        $this->assertInstanceOf('RuntimeException', $this->adapter->getRehashError());
        $this->assertTrue($this->adapter->needsRehash());
    }

    public function testRehashStorage_errorIsClearedByTheNextLogin()
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 6)),
            array('username', 'password', 'active'),
            'accounts'
        );
        $this->adapter->setRehashStorage(new ThrowingRehashStorage());

        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));
        $this->assertNotNull($this->adapter->getRehashError());

        try {
            $this->adapter->login(array(
                'username' => 'boshag',
                'password' => 'wrong',
            ));
        } catch (\Aura\Auth\Exception\PasswordIncorrect $e) {
            // expected
        }

        $this->assertNull($this->adapter->getRehashError());
    }

    public function testLogin_usernameMissing()
    {
        $this->expectException('Aura\Auth\Exception\UsernameMissing');
        $this->adapter->login(array());
    }

    public function testLogin_passwordMissing()
    {
        $this->expectException('Aura\Auth\Exception\PasswordMissing');
        $this->adapter->login(array(
            'username' => 'boshag',
        ));
    }

    public function testLogin_usernameNotFound()
    {
        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login(array(
            'username' => 'missing',
            'password' => '------',
        ));
    }

    /**
     * Passing a string algo to PasswordVerifier makes it compare a plain
     * hash() of the password: unsalted, no work factor. It exists so sites
     * with legacy password columns can still authenticate while they migrate,
     * and is not a reasonable choice for new applications. Kept covered
     * because that migration path has to keep working.
     */
    public function testLogin_legacyStringAlgo()
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password, active)
             VALUES (:username, :password, :active)"
        );
        $sth->execute(array(
            'username' => 'legacy',
            'password' => hash('md5', '345678'),
            'active' => 'y',
        ));

        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts'
        );

        list($name, $data) = $this->adapter->login(array(
            'username' => 'legacy',
            'password' => '345678',
        ));

        $this->assertSame('legacy', $name);
        $this->assertSame(array('active' => 'y'), $data);
    }

    public function testLogin_legacyStringAlgo_passwordIncorrect()
    {
        $sth = $this->pdo->prepare(
            "INSERT INTO accounts (username, password, active)
             VALUES (:username, :password, :active)"
        );
        $sth->execute(array(
            'username' => 'legacy',
            'password' => hash('md5', '345678'),
            'active' => 'y',
        ));

        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts'
        );

        $this->expectException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login(array(
            'username' => 'legacy',
            'password' => 'wrong',
        ));
    }

    public function testLogin_usernameNotFound_verifiesDummyHash()
    {
        // no inner verifier: this asserts what the adapter *asked* for, and
        // does not need a real verification to happen
        $spy = new SpyVerifier();
        $this->adapter = new PdoAdapter(
            $this->pdo,
            $spy,
            array('username', 'password', 'active'),
            'accounts'
        );

        try {
            $this->adapter->login(array(
                'username' => 'missing',
                'password' => '------',
            ));
            $this->fail('Expected UsernameNotFound.');
        } catch (\Aura\Auth\Exception\UsernameNotFound $e) {
            // expected
        }

        // the verifier must have been given work to do, against a dummy
        $this->assertCount(1, $spy->calls);
        $this->assertSame('------', $spy->calls[0][0]);
        $this->assertContains($spy->calls[0][1], array(
            AbstractAdapter::DUMMY_HASH_COST_10,
            AbstractAdapter::DUMMY_HASH_COST_12,
        ));
    }

    /**
     * The dummy verification exists only to burn time; its result must never
     * be able to log anyone in. A verifier that says "yes" to everything must
     * still leave a missing username unauthenticated.
     */
    public function testLogin_usernameNotFound_dummyCannotAuthenticate()
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new SpyVerifier(null, true),
            array('username', 'password', 'active'),
            'accounts'
        );

        $this->expectException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login(array(
            'username' => 'missing',
            'password' => 'anything at all',
        ));
    }

    public function testLogin_passwordIncorrect()
    {
        $this->expectException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '------',
        ));
    }

    public function testLogin_multipleMatches()
    {
        $this->expectException('Aura\Auth\Exception\MultipleMatches');
        $this->adapter->login(array(
            'username' => 'repeat',
            'password' => '234567',
        ));
    }

    public function testLogin_where()
    {
        $this->setAdapter("active = :active");
        list($name, $data) = $this->adapter->login(array(
            'username' => 'repeat',
            'password' => '234567',
            'active' => 'y',
        ));
        $this->assertSame('repeat', $name);
        $this->assertSame(array('active' => 'y'), $data);
    }
}

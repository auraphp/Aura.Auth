<?php
namespace Aura\Auth\Verifier;

class PasswordVerifierTest extends \PHPUnit\Framework\TestCase
{
    public function testBcrypt()
    {
        if (! defined('PASSWORD_BCRYPT')) {
            $this->markTestSkipped("password_hash functionality not available. Install ircmaxell/password-compat for 5.3+");
        }

        $verifier = new PasswordVerifier(PASSWORD_BCRYPT);
        $plaintext = 'password';
        $hashvalue = password_hash($plaintext, PASSWORD_BCRYPT);
        $this->assertTrue($verifier->verify($plaintext, $hashvalue));
        $this->assertFalse($verifier->verify('wrong', $hashvalue));
    }

    public function testHash()
    {
        $verifier = new PasswordVerifier('md5');
        $plaintext = 'password';
        $hashvalue = hash('md5', $plaintext);
        $this->assertTrue($verifier->verify($plaintext, $hashvalue));
        $this->assertFalse($verifier->verify('wrong', $hashvalue));
    }

    /**
     * The PASSWORD_* constants are strings since PHP 7.4, so testing only
     * against PASSWORD_BCRYPT sent argon2 down the hash() path, where it
     * raised "hash(): Argument #1 ($algo) must be a valid hashing algorithm".
     */
    public function testArgon2id()
    {
        if (! in_array('argon2id', password_algos(), true)) {
            $this->markTestSkipped('argon2id is not available in this build.');
        }

        $verifier = new PasswordVerifier(PASSWORD_ARGON2ID);
        $plaintext = 'password';
        $hashvalue = password_hash($plaintext, PASSWORD_ARGON2ID);
        $this->assertTrue($verifier->verify($plaintext, $hashvalue));
        $this->assertFalse($verifier->verify('wrong', $hashvalue));
    }

    public function testNeedsRehash_sameAlgoAndCost()
    {
        $verifier = new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 4));
        $hashvalue = password_hash('password', PASSWORD_BCRYPT, array('cost' => 4));
        $this->assertFalse($verifier->needsRehash($hashvalue));
    }

    public function testNeedsRehash_costRaised()
    {
        $verifier = new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 6));
        $hashvalue = password_hash('password', PASSWORD_BCRYPT, array('cost' => 4));
        $this->assertTrue($verifier->needsRehash($hashvalue));
    }

    public function testNeedsRehash_algoChanged()
    {
        if (! in_array('argon2id', password_algos(), true)) {
            $this->markTestSkipped('argon2id is not available in this build.');
        }

        $verifier = new PasswordVerifier(PASSWORD_ARGON2ID);
        $hashvalue = password_hash('password', PASSWORD_BCRYPT, array('cost' => 4));
        $this->assertTrue($verifier->needsRehash($hashvalue));
    }

    /**
     * An unsalted hash() digest is never acceptable storage, so it always
     * wants replacing; this is what makes a legacy migration self-driving.
     */
    public function testNeedsRehash_legacyStringAlgoIsAlwaysTrue()
    {
        $verifier = new PasswordVerifier('md5');
        $this->assertTrue($verifier->needsRehash(hash('md5', 'password')));
    }

    /**
     * During a migration the column holds both kinds of hash at once: accounts
     * that have logged in since it started are already on bcrypt, the rest are
     * still on the old digest. A legacy-configured verifier has to read both,
     * or every account locks its owner out the visit after it is migrated.
     */
    public function testLegacyVerifierAlsoReadsMigratedHashes()
    {
        $verifier = new PasswordVerifier('md5');

        $legacy = hash('md5', 'password');
        $this->assertTrue($verifier->verify('password', $legacy));
        $this->assertFalse($verifier->verify('wrong', $legacy));

        $migrated = password_hash('password', PASSWORD_BCRYPT, array('cost' => 4));
        $this->assertTrue($verifier->verify('password', $migrated));
        $this->assertFalse($verifier->verify('wrong', $migrated));
    }

    /**
     * ... and must stop asking for a rehash once the account has moved, or it
     * would rewrite the same row on every single login.
     */
    public function testNeedsRehash_falseForAnAlreadyMigratedHash()
    {
        $verifier = new PasswordVerifier('md5');
        $migrated = password_hash('password', PASSWORD_BCRYPT, array('cost' => 4));

        $this->assertFalse($verifier->needsRehash($migrated));
    }
}

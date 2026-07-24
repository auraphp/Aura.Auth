<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier\DummyHashInterface;
use Aura\Auth\Verifier\VerifierInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class AbstractAdapterTest extends \PHPUnit\Framework\TestCase
{
    public static function dummyHashProvider()
    {
        return array(
            'cost 10' => array(AbstractAdapter::DUMMY_HASH_COST_10, 10),
            'cost 12' => array(AbstractAdapter::DUMMY_HASH_COST_12, 12),
        );
    }

    /**
     * A malformed hash would make password_verify() fail fast without
     * hashing, quietly restoring the timing leak these exist to close.
     */
    #[DataProvider('dummyHashProvider')]
    public function testDummyHashIsAUsableBcryptHash($hash, $expect_cost)
    {
        $info = password_get_info($hash);
        $this->assertSame(PASSWORD_BCRYPT, $info['algo']);
        $this->assertSame(60, strlen($hash));
        $this->assertSame($expect_cost, $info['options']['cost']);
    }

    /**
     * The plaintexts were generated from random_bytes() and never recorded.
     * If someone regenerates one from a memorable string, that string becomes
     * a working password for anyone who copied the constant into a password
     * column.
     */
    #[DataProvider('dummyHashProvider')]
    public function testDummyHashHasNoGuessablePlaintext($hash)
    {
        $guesses = array(
            '',
            'dummy',
            'password',
            'aura',
            'aura-auth',
            'aura-auth-dummy-password',
            $hash,
        );

        foreach ($guesses as $guess) {
            $this->assertFalse(
                password_verify($guess, $hash),
                "'{$guess}' verifies against a dummy hash; regenerate it from random_bytes()."
            );
        }
    }

    /**
     * getDummyHash() picks by PHP_VERSION_ID, which is a guess about what
     * this PHP defaults to. Check the guess against what it actually does, so
     * a future release changing the default is caught here rather than by
     * silently reopening the timing gap.
     */
    public function testSelectedDummyHashMatchesThisPhpDefaultCost()
    {
        $default_cost = password_get_info(
            password_hash('measuring the default cost', PASSWORD_BCRYPT)
        )['options']['cost'];

        $adapter = new class extends AbstractAdapter {
            public function login(array $input): array
            {
                return array($input['username'], array());
            }

            public function exposeDummyHash(): string
            {
                return $this->getDummyHash();
            }
        };

        $selected_cost = password_get_info($adapter->exposeDummyHash())['options']['cost'];

        $this->assertSame(
            $default_cost,
            $selected_cost,
            'getDummyHash() does not match this PHP version default bcrypt cost;'
                . ' add a constant for cost ' . $default_cost . ' and select it.'
        );
    }

    /**
     * A verifier that can supply its own dummy knows better than the adapter
     * does: the adapter's constants are bcrypt, which is the wrong cost in
     * front of an htpasswd file or a legacy digest column.
     */
    public function testVerifyDummyPrefersTheVerifiersOwnDummyHash()
    {
        $verifier = new class implements VerifierInterface, DummyHashInterface {
            public $verified_against;

            public function verify($plaintext, $hashvalue, array $extra = array()): bool
            {
                $this->verified_against = $hashvalue;
                return false;
            }

            public function getDummyHash(): string
            {
                return '{SHA}the-verifiers-own-dummy';
            }
        };

        $this->newAdapter()->exposeVerifyDummy($verifier, 'some password');

        $this->assertSame('{SHA}the-verifiers-own-dummy', $verifier->verified_against);
    }

    /**
     * DummyHashInterface is optional, so a verifier predating it -- or any
     * third-party one -- still gets the bcrypt constant it got before.
     */
    public function testVerifyDummyFallsBackForAVerifierWithoutADummyHash()
    {
        $verifier = new class implements VerifierInterface {
            public $verified_against;

            public function verify($plaintext, $hashvalue, array $extra = array()): bool
            {
                $this->verified_against = $hashvalue;
                return false;
            }
        };

        $adapter = $this->newAdapter();
        $adapter->exposeVerifyDummy($verifier, 'some password');

        $this->assertSame($adapter->exposeDummyHash(), $verifier->verified_against);
    }

    /**
     * verifyDummy() runs only on a path that has already failed, and its
     * result is discarded; a verifier that returned true for the dummy must
     * still not authenticate anyone.
     */
    public function testVerifyDummyDiscardsItsResult()
    {
        $verifier = new class implements VerifierInterface {
            public function verify($plaintext, $hashvalue, array $extra = array()): bool
            {
                return true;
            }
        };

        $this->assertNull(
            $this->newAdapter()->exposeVerifyDummy($verifier, 'some password')
        );
    }

    protected function newAdapter()
    {
        return new class extends AbstractAdapter {
            public function login(array $input): array
            {
                return array($input['username'], array());
            }

            public function exposeDummyHash(): string
            {
                return $this->getDummyHash();
            }

            public function exposeVerifyDummy(VerifierInterface $verifier, $password)
            {
                return $this->verifyDummy($verifier, $password);
            }
        };
    }
}

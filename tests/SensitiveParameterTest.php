<?php
namespace Aura\Auth;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Aura\Auth\Adapter\PdoAdapter;
use Aura\Auth\Verifier\HtpasswdVerifier;
use Aura\Auth\Verifier\PasswordVerifier;

/**
 * Throws from inside computeContext(), so that a trace is captured while
 * computeContext() and its callers still hold the plaintext.
 */
class ThrowingContextVerifier extends HtpasswdVerifier
{
    protected function computeContext(#[\SensitiveParameter] $plaintext, $salt): string
    {
        throw new \RuntimeException('boom');
    }
}

/**
 * The same, one frame deeper: computeBinary() receives the plaintext *and* the
 * context, which is built out of the plaintext and leaks it just as directly.
 */
class ThrowingBinaryVerifier extends HtpasswdVerifier
{
    protected function computeBinary(
        #[\SensitiveParameter] $plaintext,
        $salt,
        #[\SensitiveParameter] $context
    ): string {
        throw new \RuntimeException('boom');
    }
}

/**
 * Passwords must not survive into stack traces, because traces reach log
 * files, error reporters, and error pages. PHP redacts an argument marked
 * #[\SensitiveParameter] wherever it appears in a trace, replacing it with
 * Object(SensitiveParameterValue).
 *
 * Two kinds of check here, because each catches what the other cannot:
 *
 * - The behavioural tests prove the redaction actually happens end to end,
 *   including in frames belonging to callers that merely passed the value
 *   along.
 *
 * - The declaration test pins the attribute to a named list of parameters, so
 *   that deleting one is caught even where the behavioural tests are skipped,
 *   and so that adding a credential-bearing parameter without the attribute is
 *   a deliberate act rather than an oversight.
 */
class SensitiveParameterTest extends \PHPUnit\Framework\TestCase
{
    const PLAINTEXT = 'correct-horse-battery-staple-9f3a1c';

    protected function setUp(): void
    {
        // With zend.exception_ignore_args on, PHP records no arguments in
        // traces at all, so every behavioural assertion below would pass
        // whether or not the attribute is present -- i.e. prove nothing. The
        // declaration test still runs; it does not depend on this setting.
        if (ini_get('zend.exception_ignore_args')) {
            $this->markTestSkipped(
                'zend.exception_ignore_args is on, so traces carry no '
                . 'arguments and this test cannot distinguish redacted from '
                . 'absent.'
            );
        }
    }

    /**
     * A trace holds arguments in two places that render differently:
     * getTraceAsString() abbreviates them, while getTrace() returns them
     * whole. Check both, plus the message, and walk the whole exception chain.
     */
    protected function assertTraceHasNoPlaintext(\Throwable $e, $plaintext)
    {
        for ($t = $e; $t !== null; $t = $t->getPrevious()) {
            $this->assertStringNotContainsString(
                $plaintext,
                $t->getTraceAsString(),
                'the plaintext leaked into getTraceAsString()'
            );

            $this->assertStringNotContainsString(
                $plaintext,
                print_r($t->getTrace(), true),
                'the plaintext leaked into getTrace()'
            );

            $this->assertStringNotContainsString(
                $plaintext,
                $t->getMessage(),
                'the plaintext leaked into the exception message'
            );
        }
    }

    /**
     * Guards the guard: if this fails, the assertions above are not actually
     * looking at argument values, and every other test in this file is
     * vacuous.
     */
    public function testAnUnmarkedArgumentDoesLeak()
    {
        $leak = function ($not_marked) {
            throw new \RuntimeException('boom');
        };

        try {
            $leak(self::PLAINTEXT);
            $this->fail('expected an exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                self::PLAINTEXT,
                print_r($e->getTrace(), true)
            );
        }
    }

    public function testPdoAdapterWrongPassword()
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE accounts (username VARCHAR(255), password VARCHAR(255))');
        $sth = $pdo->prepare(
            'INSERT INTO accounts (username, password) VALUES (:username, :password)'
        );
        $sth->execute(array(
            'username' => 'boshag',
            'password' => password_hash('123456', PASSWORD_BCRYPT, array('cost' => 4)),
        ));

        $adapter = new PdoAdapter(
            $pdo,
            new PasswordVerifier(PASSWORD_BCRYPT),
            array('username', 'password'),
            'accounts'
        );

        try {
            $adapter->login(array(
                'username' => 'boshag',
                'password' => self::PLAINTEXT,
            ));
            $this->fail('expected PasswordIncorrect');
        } catch (Exception\PasswordIncorrect $e) {
            $this->assertTraceHasNoPlaintext($e, self::PLAINTEXT);
        }
    }

    /**
     * The unknown-username path runs the password through verifyDummy() rather
     * than a real verification, so it is a separate route to the same secret.
     */
    public function testPdoAdapterUnknownUsername()
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE accounts (username VARCHAR(255), password VARCHAR(255))');

        $adapter = new PdoAdapter(
            $pdo,
            new PasswordVerifier(PASSWORD_BCRYPT),
            array('username', 'password'),
            'accounts'
        );

        try {
            $adapter->login(array(
                'username' => 'nobody',
                'password' => self::PLAINTEXT,
            ));
            $this->fail('expected UsernameNotFound');
        } catch (Exception\UsernameNotFound $e) {
            $this->assertTraceHasNoPlaintext($e, self::PLAINTEXT);
        }
    }

    /**
     * The htpasswd apr1 path hands the plaintext down through four frames of
     * its own, which is where an unmarked helper would show up.
     */
    public function testHtpasswdVerifierDeepFrames()
    {
        $hashvalue = '$apr1$abcdefgh$0123456789012345678901';

        foreach (array(ThrowingContextVerifier::class, ThrowingBinaryVerifier::class) as $class) {
            $verifier = new $class();

            try {
                $verifier->verify(self::PLAINTEXT, $hashvalue);
                $this->fail("expected {$class} to throw");
            } catch (\RuntimeException $e) {
                $this->assertTraceHasNoPlaintext($e, self::PLAINTEXT);
            }
        }
    }

    /**
     * Every parameter that carries a credential, and the attribute it must
     * declare. Adding a row here is how a new credential-bearing parameter
     * gets covered.
     */
    public static function provideSensitiveParameters()
    {
        return array(
            // the verifier contract and both implementations
            array('Aura\Auth\Verifier\VerifierInterface', 'verify', 'plaintext'),
            array('Aura\Auth\Verifier\PasswordVerifier', 'verify', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'verify', 'plaintext'),

            // htpasswd internals that receive the plaintext
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'sha', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'apr1', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'des', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'computeApr1', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'computeContext', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'computeBinary', 'plaintext'),
            array('Aura\Auth\Verifier\HtpasswdVerifier', 'computeBinary', 'context'),

            // the adapters: $input carries 'password'
            array('Aura\Auth\Adapter\AdapterInterface', 'login', 'input'),
            array('Aura\Auth\Adapter\AbstractAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\AbstractAdapter', 'checkInput', 'input'),
            array('Aura\Auth\Adapter\PdoAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\HtpasswdAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\LdapAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\ImapAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\OAuth2Adapter', 'login', 'input'),
            array('Aura\Auth\Adapter\HeaderAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\NullAdapter', 'login', 'input'),
            array('Aura\Auth\Adapter\ThrottleAdapter', 'login', 'input'),

            // adapter internals
            array('Aura\Auth\Adapter\PdoAdapter', 'fetchRow', 'input'),
            array('Aura\Auth\Adapter\PdoAdapter', 'verify', 'input'),
            array('Aura\Auth\Adapter\OAuth2Adapter', 'mapOwner', 'token'),
            array('Aura\Auth\Adapter\AbstractAdapter', 'verifyDummy', 'password'),
            array('Aura\Auth\Adapter\AbstractAdapter', 'applyRehash', 'plaintext'),
            array('Aura\Auth\Adapter\HtpasswdAdapter', 'verify', 'password'),
            array('Aura\Auth\Adapter\LdapAdapter', 'bind', 'password'),
            array('Aura\Auth\Adapter\LdapAdapter', 'bindSearch', 'password'),
            array('Aura\Auth\Adapter\LdapAdapter', 'ldapBind', 'password'),

            // the service that receives the credentials from the application
            array('Aura\Auth\Service\LoginService', 'login', 'input'),

            // the proxy that hands the bind password to ldap_bind()/imap_open()
            array('Aura\Auth\Phpfunc', '__call', 'params'),

            // the rehash writer sees a verified plaintext
            array('Aura\Auth\Rehash\RehashStorageInterface', 'rehash', 'plaintext'),
            array('Aura\Auth\Rehash\PdoRehashStorage', 'rehash', 'plaintext'),

            // API tokens are bearer credentials
            array('Aura\Auth\Token\TokenService', 'verify', 'value'),
            array('Aura\Auth\Token\TokenService', 'findGenuine', 'value'),
            array('Aura\Auth\Token\TokenService', 'revoke', 'value'),
            array('Aura\Auth\Token\SplitToken', 'hash', 'validator'),
            array('Aura\Auth\Token\SplitToken', 'makeValue', 'validator'),
            array('Aura\Auth\Token\SplitToken', 'parseValue', 'value'),
            array('Aura\Auth\Token\SplitToken', 'verify', 'validator'),
        );
    }

    #[DataProvider('provideSensitiveParameters')]
    public function testParameterIsMarkedSensitive($class, $method, $param_name)
    {
        $method = new \ReflectionMethod($class, $method);

        foreach ($method->getParameters() as $param) {
            if ($param->getName() !== $param_name) {
                continue;
            }

            $this->assertNotEmpty(
                $param->getAttributes(\SensitiveParameter::class),
                "{$class}::{$method->getName()}() parameter \${$param_name} "
                . 'is missing #[\\SensitiveParameter]'
            );

            return;
        }

        $this->fail(
            "{$class}::{$method->getName()}() has no parameter \${$param_name}"
        );
    }
}

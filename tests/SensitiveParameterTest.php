<?php
namespace Aura\Auth;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Aura\Auth\Adapter\OAuth2Adapter;
use Aura\Auth\Adapter\PdoAdapter;
use Aura\Auth\OAuth\AuthorizationRequest;
use Aura\Auth\OAuth\LeagueProvider;
use Aura\Auth\OAuth\ProviderInterface;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
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
 * Fails at the token exchange, which is the OAuth step most likely to throw in
 * practice: an expired code, a revoked grant, a provider that is down.
 */
class ThrowingTokenProvider implements ProviderInterface
{
    public function getAuthorizationRequest(array $options = []): AuthorizationRequest
    {
        throw new \RuntimeException('not used');
    }

    public function getAccessToken(
        #[\SensitiveParameter] string $code,
        #[\SensitiveParameter] ?string $code_verifier = null
    ) {
        throw new \RuntimeException('the token endpoint rejected the code');
    }

    public function getResourceOwner(#[\SensitiveParameter] $token): array
    {
        throw new \RuntimeException('not used');
    }
}

/**
 * Gets as far as an access token, then fails fetching the resource owner.
 */
class ThrowingOwnerProvider implements ProviderInterface
{
    public function getAuthorizationRequest(array $options = []): AuthorizationRequest
    {
        throw new \RuntimeException('not used');
    }

    public function getAccessToken(
        #[\SensitiveParameter] string $code,
        #[\SensitiveParameter] ?string $code_verifier = null
    ) {
        return SensitiveParameterTest::TOKEN;
    }

    public function getResourceOwner(#[\SensitiveParameter] $token): array
    {
        throw new \RuntimeException('the userinfo endpoint is down');
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

    const CODE = 'authorization-code-4b81de';

    const VERIFIER = 'pkce-code-verifier-7c02af';

    const TOKEN = 'access-token-e5d914';

    /**
     * With zend.exception_ignore_args on, PHP records no arguments in traces at
     * all, so a behavioural assertion would pass whether or not the attribute
     * is present -- i.e. prove nothing.
     *
     * Called per behavioural test rather than from setUp(), so that
     * testParameterIsMarkedSensitive() keeps running: reflection does not care
     * about this setting, and production-style test environments (where the
     * setting is on) are the last place the declaration guard should go quiet.
     */
    protected function requireTraceArguments()
    {
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
        $this->requireTraceArguments();

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
        $this->requireTraceArguments();

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
        $this->requireTraceArguments();

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
        $this->requireTraceArguments();

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
     * OAuth credentials are bearer credentials: an authorization code, a PKCE
     * verifier, and an access token are all enough to impersonate the user.
     * The token exchange and the userinfo call are both remote, so they are
     * among the likeliest things in the library to throw.
     */
    public function testOAuth2ProviderFailures()
    {
        $this->requireTraceArguments();

        $cases = array(
            new ThrowingTokenProvider(),
            new ThrowingOwnerProvider(),
        );

        foreach ($cases as $provider) {
            $adapter = new OAuth2Adapter($provider, array('username_field' => 'id'));

            try {
                $adapter->login(array(
                    'code' => self::CODE,
                    'code_verifier' => self::VERIFIER,
                ));
                $this->fail('expected the provider to throw');
            } catch (\RuntimeException $e) {
                $this->assertTraceHasNoPlaintext($e, self::CODE);
                $this->assertTraceHasNoPlaintext($e, self::VERIFIER);
                $this->assertTraceHasNoPlaintext($e, self::TOKEN);
            }
        }
    }

    /**
     * A `map` callback is the application's own closure, and PHP will not
     * redact the arguments of a frame the application declared. The library can
     * only keep the token out of *its* frames; documenting that boundary is the
     * best available answer, so this pins where the boundary actually falls
     * rather than asserting a guarantee that cannot be made.
     */
    public function testThrowingMapCallbackIsTheApplicationsOwnFrame()
    {
        $this->requireTraceArguments();

        // ThrowingOwnerProvider never reaches the callback, so drive the
        // mapping with a provider that returns an owner.
        $provider = new class extends ThrowingOwnerProvider {
            public function getResourceOwner(#[\SensitiveParameter] $token): array
            {
                return array('id' => 'boshag');
            }
        };

        $adapter = new OAuth2Adapter(
            $provider,
            array('map' => function ($owner, $token) {
                throw new \RuntimeException('the map callback failed');
            })
        );

        try {
            $adapter->login(array('code' => self::CODE));
            $this->fail('expected the map callback to throw');
        } catch (\RuntimeException $e) {
            // the library's own frames are clean: the authorization code is
            // redacted everywhere, and mapOwner() redacts the token it holds
            $this->assertTraceHasNoPlaintext($e, self::CODE);

            $this->assertStringContainsString(
                'Object(SensitiveParameterValue)',
                $e->getTraceAsString(),
                'mapOwner() should have redacted the token in its own frame'
            );

            // ...but the closure belongs to the application, and PHP does not
            // redact arguments of a frame the application declared. This is the
            // boundary docs/security.md describes; asserting it here means a
            // future PHP that closes the gap shows up as a failing test rather
            // than as documentation that quietly went stale.
            $this->assertStringContainsString(
                self::TOKEN,
                print_r($e->getTrace(), true),
                "the application's own callback frame is outside what the "
                . 'library can redact; if this now passes, update '
                . 'docs/security.md'
            );
        }
    }

    /**
     * Builds a real League provider whose HTTP handler throws, so that the
     * exception is constructed *below* League's own frames -- which is what
     * puts those frames, and their arguments, into the trace.
     */
    protected function newThrowingLeagueProvider()
    {
        $handler = function ($request, $options) {
            throw new \RuntimeException('the endpoint is unreachable');
        };

        return new GenericProvider(
            array(
                'clientId' => 'client-id',
                'clientSecret' => 'client-secret',
                'redirectUri' => 'https://app.example/callback',
                'urlAuthorize' => 'https://provider.example/authorize',
                'urlAccessToken' => 'https://provider.example/token',
                'urlResourceOwnerDetails' => 'https://provider.example/me',
                'pkceMethod' => 'S256',
            ),
            array('httpClient' => new Client(array('handler' => $handler)))
        );
    }

    /**
     * The same non-inheritance rule that leaves a `map` callback outside the
     * library's reach also leaves `league/oauth2-client` outside it:
     * `AbstractProvider::getAccessToken()` and `getResourceOwner()` do not mark
     * their own parameters, so when a call below them throws, League's frames
     * carry the authorization code and the access token in the clear.
     *
     * `LeagueProvider` still redacts them in *its* frames, which is as far as
     * this package can go. Pinning the boundary here means a future League
     * release that marks its parameters shows up as a failing test rather than
     * as documentation that quietly went stale.
     */
    public function testLeagueProviderIsTheDependencysOwnFrame()
    {
        $this->requireTraceArguments();

        $provider = new LeagueProvider($this->newThrowingLeagueProvider());

        try {
            $provider->getAccessToken(self::CODE, self::VERIFIER);
            $this->fail('expected the token exchange to throw');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                'Object(SensitiveParameterValue)',
                $e->getTraceAsString(),
                'LeagueProvider::getAccessToken() should have redacted the '
                . 'code and the verifier in its own frame'
            );

            // League holds the verifier as a property rather than passing it
            // along, so only the code reaches one of its frames as an argument
            $this->assertStringContainsString(
                self::CODE,
                print_r($e->getTrace(), true),
                'league/oauth2-client does not mark its own parameters; if '
                . 'this now passes, update docs/security.md'
            );
        }

        $provider = new LeagueProvider($this->newThrowingLeagueProvider());

        try {
            $provider->getResourceOwner(new AccessToken(
                array('access_token' => self::TOKEN)
            ));
            $this->fail('expected the userinfo call to throw');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                'Object(SensitiveParameterValue)',
                $e->getTraceAsString(),
                'LeagueProvider::getResourceOwner() should have redacted the '
                . 'token in its own frame'
            );

            $this->assertStringContainsString(
                self::TOKEN,
                print_r($e->getTrace(), true),
                'league/oauth2-client does not mark its own parameters; if '
                . 'this now passes, update docs/security.md'
            );
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

            // OAuth: the code, the PKCE verifier and the token are all bearer
            // credentials, and both provider calls are remote and can throw
            array('Aura\Auth\OAuth\ProviderInterface', 'getAccessToken', 'code'),
            array('Aura\Auth\OAuth\ProviderInterface', 'getAccessToken', 'code_verifier'),
            array('Aura\Auth\OAuth\ProviderInterface', 'getResourceOwner', 'token'),
            array('Aura\Auth\OAuth\LeagueProvider', 'getAccessToken', 'code'),
            array('Aura\Auth\OAuth\LeagueProvider', 'getAccessToken', 'code_verifier'),
            array('Aura\Auth\OAuth\LeagueProvider', 'getResourceOwner', 'token'),
            array('Aura\Auth\OAuth\AuthorizationRequest', '__construct', 'code_verifier'),
            array('Aura\Auth\OAuth\AuthorizationCodeFlow', 'handleCallback', 'query'),
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

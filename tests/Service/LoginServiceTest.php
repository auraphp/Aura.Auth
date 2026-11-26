<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Adapter\PdoAdapter;
use Aura\Auth\Auth;
use Aura\Auth\FakePhpfunc;
use Aura\Auth\Remember\Cookie;
use Aura\Auth\Remember\FakeRememberStorage;
use Aura\Auth\Remember\RememberService;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Status;
use Aura\Auth\Token\SplitToken;
use Aura\Auth\Verifier\PasswordVerifier;
use PDO;

class LoginServiceTest extends \PHPUnit\Framework\TestCase
{
    protected $segment;

    protected $session;

    protected $adapter;

    protected $auth;

    protected $login_service;

    protected function setUp() : void
    {
        $this->segment = new FakeSegment;
        $this->session = new FakeSession;
        $this->adapter = new FakeAdapter;

        $this->auth = new Auth($this->segment);

        $this->login_service = new LoginService(
            $this->adapter,
            $this->session
        );
    }

    public function testLogin()
    {
        $this->assertTrue($this->auth->isAnon());

        $this->login_service->login(
            $this->auth,
            array('username' => 'boshag')
        );

        $this->assertTrue($this->auth->isValid());
        $this->assertSame('boshag', $this->auth->getUserName());
    }

    public function testLogin_doesNotPassRememberToAdapter()
    {
        $this->login_service->login($this->auth, array(
            'username' => 'boshag',
            'password' => '123456',
            'remember' => true,
        ));

        $this->assertSame(
            array('username' => 'boshag', 'password' => '123456'),
            $this->adapter->input
        );
    }

    public function testLogin_rememberWithPdoAdapter()
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Requires the pdo_sqlite extension.');
        }

        // a real PDO, since only a real driver rejects a bound value that
        // has no matching placeholder in the statement
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE accounts (username VARCHAR(255), password VARCHAR(255))');
        $pdo->prepare('INSERT INTO accounts (username, password) VALUES (?, ?)')
            ->execute(array(
                'boshag',
                password_hash('123456', PASSWORD_BCRYPT, array('cost' => 4)),
            ));

        $storage = new FakeRememberStorage;
        $phpfunc = new FakePhpfunc;
        $login_service = new LoginService(
            new PdoAdapter(
                $pdo,
                new PasswordVerifier(PASSWORD_BCRYPT),
                array('username', 'password'),
                'accounts'
            ),
            $this->session,
            new RememberService(
                $storage,
                $this->session,
                new SplitToken($phpfunc),
                new Cookie($phpfunc, array()),
                'remember',
                2592000
            )
        );

        $login_service->login($this->auth, array(
            'username' => 'boshag',
            'password' => '123456',
            'remember' => true,
        ));

        $this->assertTrue($this->auth->isValid());
        $this->assertSame('boshag', $this->auth->getUserName());
        $this->assertCount(1, $storage->rows);
        $this->assertArrayHasKey('remember', $phpfunc->cookies);
    }

    public function testForceLogin()
    {
        $this->assertTrue($this->auth->isAnon());

        $result = $this->login_service->forceLogin(
            $this->auth,
            'boshag',
            array('foo' => 'bar')
        );

        $this->assertSame(Status::VALID, $result);
        $this->assertSame(Status::VALID, $this->auth->getStatus());
        $this->assertSame('boshag', $this->auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $this->auth->getUserData());
    }

    public function testForceLogin_cannotResumeOrStart()
    {
        $this->session->allow_resume = false;
        $this->session->allow_start = false;

        $this->assertTrue($this->auth->isAnon());

        $result = $this->login_service->forceLogin(
            $this->auth,
            'boshag',
            array('foo' => 'bar')
        );

        $this->assertFalse($result);
        $this->assertTrue($this->auth->isAnon());
    }
}

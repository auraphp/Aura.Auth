<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Session\SessionFactory;

/**
 * Integration test wiring Aura.Auth against the real Aura.Session
 * implementation of the shared Session_Interface contracts, driving a
 * full login -> resume -> logout cycle through an actual PHP session.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SessionIntegrationTest extends \PHPUnit\Framework\TestCase
{
    protected $session;

    protected $segment;

    protected $factory;

    protected $handler;

    protected function setUp() : void
    {
        // keep the real session in memory instead of on disk / in headers
        $this->handler = new FakeSessionHandler;
        session_set_save_handler($this->handler, true);

        $this->session = (new SessionFactory)->newInstance($_COOKIE);
        $this->segment = $this->session->getSegment('Aura\Auth\Auth');

        $this->factory = new AuthFactory($_COOKIE, $this->session, $this->segment);
    }

    protected function tearDown() : void
    {
        $_SESSION = array();
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function testLoginPersistsThroughRealSession()
    {
        $auth = $this->factory->newInstance();
        $login = $this->factory->newLoginService(new FakeAdapter);

        $this->assertTrue($auth->isAnon());

        $login->login($auth, array('username' => 'boshag'));

        // the auth tracker reflects the login...
        $this->assertTrue($auth->isValid());
        $this->assertSame('boshag', $auth->getUserName());

        // ...and the real Aura.Session segment actually stored it in $_SESSION
        $this->assertSame(Status::VALID, $_SESSION['Aura\Auth\Auth']['status']);
        $this->assertSame('boshag', $_SESSION['Aura\Auth\Auth']['username']);
    }

    public function testResumeSeesPreviouslyStoredSession()
    {
        // first "request": log the user in
        $login = $this->factory->newLoginService(new FakeAdapter);
        $login->login($this->factory->newInstance(), array('username' => 'boshag'));

        // second "request": a fresh Auth reading the same session segment
        $auth = $this->factory->newInstance();
        $resume = $this->factory->newResumeService(new FakeAdapter);
        $resume->resume($auth);

        $this->assertTrue($auth->isValid());
        $this->assertSame('boshag', $auth->getUserName());
    }

    public function testLogoutClearsRealSession()
    {
        $auth = $this->factory->newInstance();
        $login = $this->factory->newLoginService(new FakeAdapter);
        $login->login($auth, array('username' => 'boshag'));
        $this->assertTrue($auth->isValid());

        $logout = $this->factory->newLogoutService(new FakeAdapter);
        $logout->logout($auth);

        $this->assertTrue($auth->isAnon());
        $this->assertSame(Status::ANON, $_SESSION['Aura\Auth\Auth']['status']);
    }
}

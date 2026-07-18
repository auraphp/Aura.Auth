<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Session_Interface\SessionInterface;
use Aura\Auth\Remember\RememberService;
use Aura\Auth\Status;
use Aura\Auth\Auth;

/**
 *
 * Login handler
 *
 * @package Aura.Auth
 *
 */
class LoginService
{
    /**
     *
     * Adapter of Adapterinterface
     *
     * @var mixed
     *
     */
    protected $adapter;

    /**
     *
     * session
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     * An optional "remember me" handler.
     *
     * @var RememberService|null
     *
     */
    protected $remember_service;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter A credential-storage adapter.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param RememberService $remember_service An optional "remember me"
     * handler; when present, a truthy `remember` input issues a token.
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        SessionInterface $session,
        ?RememberService $remember_service = null
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
        $this->remember_service = $remember_service;
    }

    /**
     *
     * Logs the user in via the credential adapter.
     *
     * @param Auth $auth The authentication tracking object.
     *
     * @param array $input The credential input.
     *
     * @return null
     *
     */
    public function login(Auth $auth, array $input)
    {
        list($name, $data) = $this->adapter->login($input);
        $remember = ! empty($input['remember']);
        $this->forceLogin($auth, $name, $data, Status::VALID, $remember);
    }

    /**
     *
     * Forces a successful login.
     *
     * @param Auth $auth The authentication tracking object.
     *
     * @param string $name The authenticated user name.
     *
     * @param array $data Additional arbitrary user data.
     *
     * @param string $status The new authentication status.
     *
     * @param bool $remember When true and a RememberService is present, issue a
     * "remember me" token.
     *
     * @return string|false The authentication status on success, or boolean
     * false on failure.
     *
     */
    public function forceLogin(
        Auth $auth,
        $name,
        array $data = array(),
        $status = Status::VALID,
        $remember = false
    ) {
        $started = $this->session->resume() || $this->session->start();
        if (! $started) {
            return false;
        }

        $this->session->regenerateId();
        $auth->set(
            $status,
            time(),
            time(),
            $name,
            $data
        );

        if ($remember && $this->remember_service) {
            $this->remember_service->remember($auth);
        }

        return $status;
    }
}

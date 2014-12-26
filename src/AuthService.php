<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;
use Aura\Auth\Service\LoginService;
use Aura\Auth\Service\LogoutService;
use Aura\Auth\Service\ResumeService;
use Aura\Auth\Status;

class AuthService
{
    private $auth;

    private $login_service;

    private $logout_service;

    private $resume_service;

    private $resumed = false;

    private $adapter;

    public function __construct(Auth $auth, LoginService $login_service, LogoutService $logout_service, ResumeService $resume_service, AdapterInterface $adapter)
    {
        $this->auth = $auth;
        $this->login_service  = $login_service;
        $this->logout_service = $logout_service;
        $this->resume_service = $resume_service;
        $this->adapter = $adapter;
    }

    public function login(array $input)
    {
        return $this->login_service->login($this->auth, $input);
    }

    public function forceLogin(
        $name,
        array $data = array(),
        $status = Status::VALID
    ) {
        return $this->login_service->forceLogin($this->auth, $name, $data, $status);
    }

    public function logout($status = Status::ANON)
    {
        return $this->logout_service->logout($this->auth, $status);
    }

    public function forceLogout($status = Status::ANON)
    {
        return $this->logout_service->forceLogout($this->auth, $status);
    }

    /**
     *
     * Magic call to all auth related methods
     *
     */
    public function __call($method, array $params)
    {
        $this->resume();
        return call_user_func_array(array($this->auth, $method), $params);
    }

    public function getAuth()
    {
        $this->resume();
        return $this->auth;
    }

    protected function resume()
    {
        if (! $this->resumed) {
            $this->resume_service->resume($this->auth);
            $this->resumed = true;
        }
    }
}

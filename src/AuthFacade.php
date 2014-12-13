<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Status;

class AuthFacade
{
    private $auth;

    private $auth_factory;

    private $resume_service;

    public function __construct(AuthFactory $auth_factory, AdapterInterface $adapter)
    {
        $this->auth_factory = $auth_factory;
        $this->adapter = $adapter;
    }

    public function login(array $input)
    {
        $login_service = $this->getLoginService();
        return $login_service->login($this->getAuth(), $input);
    }

    public function logout($status = Status::ANON)
    {
        $logout_service = $this->getLogoutService();
        return $logout_service->logout($this->getAuth());
    }

    public function forceLogout($status = Status::ANON)
    {
        $logout_service = $this->getLogoutService();
        return $logout_service->forceLogout($this->getAuth(), $status);
    }

    public function forceLogin(
        $name,
        array $data = array(),
        $status = Status::VALID
    ) {
        $login_service = $this->getLoginService();
        return $login_service->forceLogin($this->getAuth(), $name, $data, $status);
    }

    public function __call($method, array $params)
    {
        $this->getResumeService();
        return call_user_func_array(array($this->getAuth(), $method), $params);
    }

    private function getAuth()
    {
        if (! $this->auth) {
            $this->auth = $this->auth_factory->newInstance();
        }
        return $this->auth;
    }

    private function getLoginService()
    {
        return $this->auth_factory->newLoginService($this->adapter);
    }

    private function getLogoutService()
    {
        return $this->auth_factory->newLogoutService($this->adapter);
    }

    private function getResumeService()
    {
        // only resume on first call
        if (! $this->resume_service) {
            $this->resume_service = $this->auth_factory->newResumeService($this->adapter);
            $this->resume_service->resume($this->getAuth());
        }
    }
}

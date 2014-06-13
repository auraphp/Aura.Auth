<?php
namespace Aura\Auth\Handler;

use Aura\Auth;
use Aura\Auth\AdapterInterface;

class LoginHandler extends AbstractHandler
{
    /**
     *
     * Logs the user in via the adapter.
     *
     * On success, this will start the session and populate it with the user
     * and info returned by the adapter. On failure, it will populate
     * the error property with the error value reported by the adapter.
     *
     * @param mixed $cred The credentials to pass to the adapter.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function login($cred)
    {
        $success = $this->adapter->login($cred);
        if ($success) {
            $this->auth->forceLogin(
                $this->adapter->getUser(),
                $this->adapter->getInfo()
            );
            return true;
        }

        return false;
    }
}

<?php
namespace Aura\Auth\Handler;

use Aura\Auth;
use Aura\Auth\AdapterInterface;

class LogoutHandler extends AbstractHandler
{
    /**
     *
     * Logs the user out via the adapter.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function logout()
    {
        $success = $this->adapter->logout(
            $this->auth->getUser(),
            $this->auth->getInfo()
        );

        if ($success) {
            $this->auth->forceLogout();
            return true;
        }

        return false;
    }
}

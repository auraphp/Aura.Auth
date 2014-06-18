<?php
namespace Aura\Auth\Session;

class Session implements SessionInterface
{
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function start()
    {
        return session_start();
    }

    public function resume()
    {
        if (session_id() !== '') {
            return true;
        }

        if (isset($this->cookies[session_name()])) {
            return $this->start();
        }

        return false;
    }

    public function regenerateId()
    {
        return session_regenerate_id(true);
    }

    public function destroy()
    {
        $destroyed = session_destroy();
        if ($destroyed) {
            unset($this->cookies[session_name()]);
        }
        return $destroyed;
    }
}

<?php
namespace Aura\Auth\Session;

class SessionManager implements SessionManagerInterface
{
    protected $start;

    protected $resume;

    protected $regenerate_id;

    public function __construct(
        array $cookies,
        $start = null,
        $resume = null,
        $regenerate_id = null
    ) {
        $this->cookies = $cookies;
        $this->start = $start;
        $this->resume = $resume;
        $this->regenerate_id = $regenerate_id;
    }

    public function start()
    {
        if ($this->start) {
            return call_user_func($this->start);
        }

        if (session_id() === '') {
            return session_start();
        }

        return false;
    }

    public function resume()
    {
        if ($this->resume) {
            return call_user_func($this->resume);
        }

        if (isset($this->cookies[session_name()])) {
            return $this->start();
        }

        return false;
    }

    public function regenerateId()
    {
        if ($this->regenerate_id) {
            return call_user_func($this->regenerate_id);
        }

        return session_regenerate_id(true);
    }
}

<?php
namespace Aura\Auth\Session;

class FakeSession implements SessionInterface
{
    public $started = false;
    public $resumed = false;
    public $session_id = 1;
    public $destroyed = false;


    public $allow_start = true;
    public $allow_resume = true;
    public $allow_destroy = true;

    public function start()
    {
        $this->started = $this->allow_start;
        return $this->started;
    }

    public function resume()
    {
        $this->resumed = $this->allow_resume;
        return $this->resumed;
    }

    public function regenerateId()
    {
        $this->session_id ++;
    }

    public function destroy()
    {
        $this->destroyed = $this->allow_destroy;
        return $this->destroyed;
    }
}

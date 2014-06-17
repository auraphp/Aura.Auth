<?php
namespace Aura\Auth\Session;

class FakeSession implements SessionInterface
{
    public $start = false;
    public $resume = false;
    public $session_id = 1;

    public function start()
    {
        $this->started = true;
    }

    public function resume()
    {
        $this->resume = true;
    }

    public function regenerateId()
    {
        $this->session_id ++;
    }
}

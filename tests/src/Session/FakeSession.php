<?php
namespace Aura\Auth\Session;

class FakeSession implements SessionInterface
{
    public $start = false;
    public $resume = false;
    public $session_id = 1;
    public $destroy = false;

    public function start()
    {
        $this->started = true;
        return true;
    }

    public function resume()
    {
        $this->resume = true;
        return true;
    }

    public function regenerateId()
    {
        $this->session_id ++;
    }

    public function destroy()
    {
        $this->destroy = true;
        return true;
    }
}

<?php
namespace Aura\Auth;

class FakeSession implements SessionInterface
{
    public $__started = false;

    public $__regenerated = false;

    public $status;
    public $initial;
    public $active;
    public $user;
    public $info;

    public function start()
    {
        $this->started = true;
    }

    public function regenerateId()
    {
        $this->regenerated = true;
    }
}

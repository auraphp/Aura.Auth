<?php
namespace Aura\Auth;

class FakeSession implements SessionInterface
{
    public $__started = false;

    public $__regenerated = false;

    public function __get($key)
    {
        return null;
    }

    public function start()
    {
        $this->started = true;
    }

    public function regenerateId()
    {
        $this->regenerated = true;
    }

    public function destroy()
    {
        $this->started = false;
    }
}

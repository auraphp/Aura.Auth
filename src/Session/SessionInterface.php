<?php
namespace Aura\Auth\Session;

interface SessionInterface
{
    public function start();
    public function resume();
    public function regenerateId();
    public function destroy();
}

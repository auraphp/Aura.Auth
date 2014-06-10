<?php
namespace Aura\Auth;

interface SessionInterface
{
    public function start();
    public function regenerateId();
    public function destroy();
}

<?php
namespace Aura\Auth\Session;

interface SessionManagerInterface
{
    // start only if not already started
    public function start();

    // start only if none already exists
    public function resume();

    // regenerate the session id
    public function regenerateId();
}

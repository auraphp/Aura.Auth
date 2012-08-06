<?php

namespace Aura\Auth;

function session_start()
{
    $GLOBALS['session_start'] = true;
    $_SESSION = [];
    return true;;
}

function session_regenerate_id()
{
    $GLOBALS['session_regenerate_id'] = true;

    return true;;
}
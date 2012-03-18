<?php

namespace Aura\Auth\Adapter;

function extension_loaded($ext)
{
    $return = empty($GLOBALS['extension_loaded']) ? true : $GLOBALS['extension_loaded'];
    $GLOBALS['extension_loaded'] = true;

    return $return;
}
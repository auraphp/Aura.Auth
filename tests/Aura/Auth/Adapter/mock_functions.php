<?php

namespace Aura\Auth\Adapter;

function extension_loaded($ext)
{
    $return = isset($GLOBALS['extension_loaded']) ? $GLOBALS['extension_loaded'] : \extension_loaded($ext);
    
    unset($GLOBALS['extension_loaded']);

    return $return;
}

function fopen($file, $flag)
{
    if (! isset($GLOBALS['fopen'])) {
        return \fopen($file, $flag);
    }

    $return = $GLOBALS['fopen'];
    unset($GLOBALS['fopen']);

    return $return;
}
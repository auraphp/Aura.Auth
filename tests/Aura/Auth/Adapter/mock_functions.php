<?php

namespace Aura\Auth\Adapter;

function extension_loaded($ext)
{
    $return = empty($GLOBALS['extension_loaded']) ? \extension_loaded($ext) : $GLOBALS['extension_loaded'];
    
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
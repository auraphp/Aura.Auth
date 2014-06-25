<?php
// autoloader
require dirname(__DIR__) . '/autoload.php';

// load composer autoload. Good to test password_hash really by ircmaxell/password-compat
if (is_readable(dirname(__DIR__) . '/vendor/autoload.php')) {
    require dirname(__DIR__) . '/vendor/autoload.php';
}

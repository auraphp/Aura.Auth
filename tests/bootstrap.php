<?php
spl_autoload_register(function($class) {
    $dir   = dirname(__DIR__);
    $file  = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $src = $dir . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR . $file;
    if (file_exists($src)) {
        require $src;
    }
    $tests = $dir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $file;
    if (file_exists($tests)) {
        require $tests;
    }
});
error_reporting(-1);
$GLOBALS['Aura\Auth\Adapter\Mail'] = [
    'mailbox'  => '', // 'imap.gmail.com:993/imap/ssl/novalidate-cert',
    'username' => '',
    'password' => '',
];
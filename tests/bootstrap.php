<?php

spl_autoload_register(function($class) {
    
    // split the class into namespace parts
    $parts = explode('\\', $class);
    if (count($parts) == 1) {
        return;
    }
    
    // the eventual filename
    $file = implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    
    // the package dir for the class
    $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "{$parts[0]}.{$parts[1]}";
    
    // look for a src file
    $src = $dir . DIRECTORY_SEPARATOR . 'src'. DIRECTORY_SEPARATOR . $file;
    if (is_readable($src)) {
        require $src;
    }
    
    // look for a tests file
    $tests = $dir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . $file;
    if (is_readable($tests)) {
        require $tests;
    }
});

// have to buffer; otherwise, output from PHPUnit will cause "cannot send
// headers" errors.
ob_start();

// setup for mail adapter test
$GLOBALS['Aura\Auth\Adapter\Mail'] = [
    'mailbox'  => '', // 'imap.gmail.com:993/imap/ssl/novalidate-cert',
    'username' => '',
    'password' => '',
];
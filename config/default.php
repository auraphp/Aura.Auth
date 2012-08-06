<?php

$loader->add('Aura\Auth\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/*
$di->params['Aura\Auth\AdapterFactory'] = [
    'adapters' => [
        'ini' => function() use ($di) {
            return $di->newInstance('Aura\Auth\Adapter\Ini', [ 
                'user' => $di->newInstance('Aura\Auth\User'),
                'file' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'auth.ini'
            ]);
        },
    ]
];
*/

$di->params['Aura\Auth\Auth'] = [
    'adapter_factory' => $di->newInstance('Aura\Auth\AdapterFactory')
];

$di->set('auth_adapter_factory', function() use ($di) {
    return $di->newInstance('Aura\Auth\AdapterFactory');
});

$di->set('aura_auth', function() use ($di) {
    return $di->newInstance('Aura\Auth\Auth');
});
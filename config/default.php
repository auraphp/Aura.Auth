<?php

$loader->add('Aura\Auth\\', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

$di->params['Aura\Auth\Auth'] = [
    'adapter_factory' => $di->lazyGet('auth_adapter_factory'),
    'session'         => $di->lazyGet('session_manager')
];

$di->set('auth_adapter_factory', $di->lazyNew('Aura\Auth\AdapterFactory'));

$di->set('aura_auth', $di->lazyNew('Aura\Auth\Auth'));
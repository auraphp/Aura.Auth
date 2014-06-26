# Configuration

Configuring Aura.Auth to make use of Aura.Di is a bit hard when 
the user requirements are not known and different.

Eg : What sort of hashing is used?

We prefer users to make use of the [password_hash()](http://php.net/password_hash) 
function available in PHP 5.5+ or make use of the compatibile 
library for PHP 5.3.7+ via [ircmaxell/password-compat](https://github.com/ircmaxell/password_compat)

What adapter is needed?

Aura.Auth provides different adapters. So we don’t know what 
adapter is going to be used.

## PDO Adapter

```php
$di->params['Aura\Auth\Adapter\PdoAdapter'] = array(
    'pdo' => $di->lazyGet('pdo'),
    'verifier' => $di->lazyGet('aura_auth_password_verifier'),
    'cols' => array(
        'username',
        'password'
    ),
    'from' => 'tablename',
    'where' => 'active=1'
);
```

## Htpasswd Adapter

```php
$di->params['Aura\Auth\Adapter\HtpasswdAdapter'] = array(
    'file' => '//filepath',
    'verifier' => $di->lazyGet('aura_auth_htpasswd_verifier')
);
```

## Ini Adapter

```php
$di->params['Aura\Auth\Adapter\IniAdapter'] = array(
    'file' => '//filepath',
    'verifier' => $di->lazyGet('aura_auth_password_verifier')
);
```

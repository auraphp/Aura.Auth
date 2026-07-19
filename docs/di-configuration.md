# DI Configuration

Here are some hints regarding configuration of Aura.Auth via Aura.Di.

> N.b.: This package no longer ships a `Common` config class, so nothing is
> pre-wired for you. Every constructor parameter has to be supplied, including
> the collaborators earlier versions filled in by default — the `verifier` on
> the password-checking adapters, and the `phpfunc` proxy on the adapters that
> call PHP's extension functions. The examples below spell all of them out.

## Aura\Auth\Adapter\HtpasswdAdapter

```php
$di->params['Aura\Auth\Adapter\HtpasswdAdapter'] = array(
    'file' => '/path/to/htpasswdfile',
    'verifier' => $di->lazyNew('Aura\Auth\Verifier\HtpasswdVerifier'),
);
```

## Aura\Auth\Adapter\ImapAdapter

```php
$di->params['Aura\Auth\Adapter\ImapAdapter'] = array(
    'phpfunc' => $di->lazyNew('Aura\Auth\Phpfunc'),
    'mailbox' => '{mail.example.com:143/imap/secure}',
);
```

## Aura\Auth\Adapter\LdapAdapter

```php
$di->params['Aura\Auth\Adapter\LdapAdapter'] = array(
    'phpfunc' => $di->lazyNew('Aura\Auth\Phpfunc'),
    'server' => 'ldaps://ldap.example.com:636',
    'dnformat' => 'ou=Company Name,dc=Department Name,cn=users,uid=%s',
);
```

To use the "bind, search, rebind" pattern (see [Adapters](adapters.md)), add the
optional `options` and `search` params. When `search` is given, `dnformat` is
ignored:

```php
$di->params['Aura\Auth\Adapter\LdapAdapter'] = array(
    'phpfunc' => $di->lazyNew('Aura\Auth\Phpfunc'),
    'server' => 'ldaps://ldap.example.com:636',
    'dnformat' => 'uid=%s,dc=example,dc=org', // ignored when 'search' is set
    'options' => array(
        LDAP_OPT_PROTOCOL_VERSION => 3,
        LDAP_OPT_REFERRALS => 0,
    ),
    'search' => array(
        'binddn' => 'cn=service,dc=example,dc=org',
        'bindpw' => 'service-account-password',
        'basedn' => 'dc=example,dc=org',
        'filter' => '(uid=%s)',
        'attributes' => array('cn', 'mail'),
    ),
);
```

## Aura\Auth\Adapter\PdoAdapter

```php
$di->params['Aura\Auth\Adapter\PdoAdapter'] = array(
    'pdo' => $di->lazyGet('your_pdo_connection_service'),
    'verifier' => $di->lazyNew(
        'Aura\Auth\Verifier\PasswordVerifier',
        array('algo' => PASSWORD_BCRYPT)
    ),
    'cols' => array(
        'username_column',
        'password_column',
    ),
    'from' => 'users_table',
    'where' => '',
);
```

## Aura\Auth\Remember\RememberService

See [Remember Me](remember-me.md) for what these pieces do.

```php
$di->params['Aura\Auth\Remember\PdoRememberStorage'] = array(
    'pdo' => $di->lazyGet('your_pdo_connection_service'),
    'table' => 'aura_auth_remember',
);

$di->params['Aura\Auth\Remember\Cookie'] = array(
    'phpfunc' => $di->lazyNew('Aura\Auth\Phpfunc'),
    'cookie' => $_COOKIE,
);

$di->params['Aura\Auth\Remember\RememberService'] = array(
    'storage' => $di->lazyNew('Aura\Auth\Remember\PdoRememberStorage'),
    'session' => $di->lazyGet('your_session_service'),
    'token' => $di->lazyNew('Aura\Auth\Token\SplitToken', array(
        'phpfunc' => $di->lazyNew('Aura\Auth\Phpfunc'),
    )),
    'cookie' => $di->lazyNew('Aura\Auth\Remember\Cookie'),
    'ttl' => 2592000,
);
```

## Aura\Auth\Adapter\ThrottleAdapter

See [Login Throttling](throttling.md). The adapter wraps another adapter, so
`adapter` is whichever one actually checks credentials:

```php
$di->params['Aura\Auth\Throttle\PdoThrottleStorage'] = array(
    'pdo' => $di->lazyGet('your_pdo_connection_service'),
    'table' => 'aura_auth_throttle',
    'window' => 900,
);

$di->params['Aura\Auth\Throttle\ThrottleService'] = array(
    'storage' => $di->lazyNew('Aura\Auth\Throttle\PdoThrottleStorage'),
    'options' => array('max_attempts' => 5, 'cap' => 900),
);

$di->params['Aura\Auth\Adapter\ThrottleAdapter'] = array(
    'adapter' => $di->lazyNew('Aura\Auth\Adapter\PdoAdapter'),
    'throttle' => $di->lazyNew('Aura\Auth\Throttle\ThrottleService'),
);
```

## Aura\Auth\Adapter\HeaderAdapter

See [API Tokens](api-tokens.md).

```php
$di->params['Aura\Auth\Token\PdoTokenStorage'] = array(
    'pdo' => $di->lazyGet('your_pdo_connection_service'),
    'table' => 'aura_auth_token',
    'track_last_used' => false,
);

$di->params['Aura\Auth\Token\TokenService'] = array(
    'storage' => $di->lazyNew('Aura\Auth\Token\PdoTokenStorage'),
    'token' => $di->lazyNew('Aura\Auth\Token\SplitToken', array(
        'phpfunc' => $di->lazyNew('Aura\Auth\Phpfunc'),
    )),
    'ttl' => 2592000,
);

$di->params['Aura\Auth\Adapter\HeaderAdapter'] = array(
    'token_service' => $di->lazyNew('Aura\Auth\Token\TokenService'),
    'server' => $_SERVER,
    'options' => array('header' => 'HTTP_AUTHORIZATION', 'prefix' => 'Bearer '),
);

$di->params['Aura\Auth\Service\ApiResumeService'] = array(
    'adapter' => $di->lazyNew('Aura\Auth\Adapter\HeaderAdapter'),
);
```

> N.b.: An `Auth` used for stateless requests must be built with an
> `ArraySegment`, not the session segment, or its writes are discarded:
>
> ```php
> $api_auth = $di->lazyNew('Aura\Auth\Auth', array(
>     'segment' => $di->lazyNew('Aura\Auth\Session\ArraySegment'),
> ));
> ```

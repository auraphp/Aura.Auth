# DI Configuration

Here are some hints regarding configuration of Aura.Auth via Aura.Di.

## Aura\Auth\Adapter\HtpasswdAdapter

```php
<?php
$di->params['Aura\Auth\Adapter\HtpasswdAdapter'] = array(
    'file' => '/path/to/htpasswdfile',
);
?>
```

## Aura\Auth\Adapter\ImapAdapter

```php
<?php
$di->params['Aura\Auth\Adapter\ImapAdapter'] = array(
    'mailbox' => '{mail.example.com:143/imap/secure}',
);
?>
```

## Aura\Auth\Adapter\LdapAdapter

```php
<?php
$di->params['Aura\Auth\Adapter\LdapAdapter'] = array(
    'server' => 'ldaps://ldap.example.com:636',
    'dnformat' => 'ou=Company Name,dc=Department Name,cn=users,uid=%s',
);
?>
```

To use the "bind, search, rebind" pattern (see [Adapters](adapters.md)), add the
optional `options` and `search` params. When `search` is given, `dnformat` is
ignored:

```php
<?php
$di->params['Aura\Auth\Adapter\LdapAdapter'] = array(
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
?>
```

## Aura\Auth\Adapter\PdoAdapter

```php
<?php
$di->params['Aura\Auth\Adapter\PdoAdapter'] = array(
    'pdo' => $di->lazyGet('your_pdo_connection_service'),
    'cols' => array(
        'username_column',
        'password_column',
    ),
    'from' => 'users_table',
    'where' => '',
);
?>
```

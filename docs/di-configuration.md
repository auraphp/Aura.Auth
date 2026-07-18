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

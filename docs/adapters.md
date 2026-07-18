# Adapters

Forcing the _Auth_ object to a particular state is fine for when you want to exercise manual control over the authentication status, user name, user data, and other information. However, it is more often the case that you will want to check user credential input (username and password) against a credential store.  This is where the _Adapter_ classes come in.

To use an _Adapter_ with a _Service_, you first need to create the _Adapter_, then pass it to the _AuthFactory_ `new*Service()` method.

## Htpasswd Adapter

### Instantiation

To create an adapter for Apache htpasswd files, call the _AuthFactory_ `newHtpasswdAdapter()` method and pass the file path of the Apache htpasswd file.

```php
<?php
$htpasswd_adapter = $auth_factory->newHtpasswdAdapter(
    '/path/to/accounts.htpasswd'
);
?>
```

This will automatically use the _HtpasswdVerifier_ to check DES, MD5, and SHA passwords from the htpasswd file on a per-user basis.

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($htpasswd_adapter);
$logout_service = $auth_factory->newLogoutService($htpasswd_adapter);
$resume_service = $auth_factory->newResumeService($htpasswd_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

## IMAP/POP/NNTP Adapter

### Instantiation

To create an adapter for IMAP/POP/NNTP servers, call the _AuthFactory_ `newImapAdapter()` method and pass the mailbox specification string, along with any appropriate option constants:

```php
<?php
$imap_adapter = $auth_factory->newImapAdapter(
    '{mail.example.com:143/imap/secure}',
    OP_HALFOPEN
);
?>
```

> N.b.: See the [imap_open()](http://php.net/imap_open) documentation for more variations on mailbox specification strings.

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($imap_adapter);
$logout_service = $auth_factory->newLogoutService($imap_adapter);
$resume_service = $auth_factory->newResumeService($imap_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

## LDAP Adapter

### Instantiation

To create an adapter for LDAP and Active Directory servers, call the _AuthFactory_ `newLdapAdapter()` method and pass the server name with a distinguished name (DN) format string:

```php
<?php
$ldap_adapter = $auth_factory->newLdapAdapter(
    'ldaps://ldap.example.com:636',
    'ou=Company Name,dc=Department Name,cn=users,uid=%s'
);
?>
```

> N.b.: The username will be escaped and then passed to the DN format string via [sprintf()](http://php.net/sprintf). The completed DN will be used for binding to the server after connection.

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($ldap_adapter);
$logout_service = $auth_factory->newLogoutService($ldap_adapter);
$resume_service = $auth_factory->newResumeService($ldap_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

## PDO Adapter

### Instantiation

To create an adapter for PDO connections to SQL tables, call the _AuthFactory_ `newPdoAdapter()` method and pass these parameters in order:

- a _PDO_ connection instance

- a indication of how passwords are hashed in the database:

    - if a `PASSWORD_*` constant from PHP 5.5 and up, it is treated as `password_hash()` algorithm for a _PasswordVerifier_ instance (this is the preferred method)

    - if a string, it is treated as a `hash()` algorithm for a _HashVerifier_ instance

    - otherwise, it is expected to be an implementation of _VerifierInterface_

- an array of column names: the first element is the username column, the second element is the hashed-password column, and additional columns are used as extra user information to be selected and returned from the database

- a `FROM` specification string to indicate one or more table names, with any other `JOIN` clauses you wish to add

- an optional `WHERE` condition string; use this to add extra conditions to the `SELECT` statement built by the adapter

Here is a legacy example where passwords are MD5 hashed in an accounts table:

```php
<?php
$pdo = new \PDO(...);
$hash = new PasswordVerifier('md5');
$cols = array('username', 'md5password');
$from = 'accounts';
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from);
?>
```

Here is a modern, more complex example that uses bcrypt instead of md5, retrieves extra user information columns from joined tables, and filters for active accounts:

```php
<?php
$pdo = new \PDO(...);
$hash = new PasswordVerifier(PASSWORD_BCRYPT);
$cols = array(
    'accounts.username', // "AS username" is added by the adapter
    'accounts.bcryptpass', // "AS password" is added by the adapter
    'accounts.uid AS uid',
    'userinfo.email AS email',
    'userinfo.uri AS website',
    'userinfo.fullname AS display_name',
);
$from = 'accounts JOIN profiles ON accounts.uid = profiles.uid';
$where = 'accounts.active = 1';
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from, $where);
?>
```

(The additional information columns will be retained in the session data after successful authentication.)

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($pdo_adapter);
$logout_service = $auth_factory->newLogoutService($pdo_adapter);
$resume_service = $auth_factory->newResumeService($pdo_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)


## Custom Adapters

Although this package comes with multiple _Adapter_ classes, it may be that none of them fit your needs.

You may wish to extend one of the existing adapters to add login/logout/resume behaviors. Alternatively, you can create an _Adapter_ of your own by implementing the _AdapterInterface_ on a class of your choosing:

```php
<?php
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;
use Aura\Auth\Status;

class CustomAdapter implements AdapterInterface
{
    // AdapterInterface::login()
    public function login(array $input)
    {
        if ($this->isLegit($input)) {
            $username = ...;
            $userdata = array(...);
            $this->updateLoginTime(time());
            return array($username, $userdata);
        } else {
            throw CustomException('Something went wrong.');
        }
    }

    // AdapterInterface::logout()
    public function logout(Auth $auth, $status = Status::ANON)
    {
        $this->updateLogoutTime($auth->getUsername(), time());
    }

    // AdapterInterface::resume()
    public function resume(Auth $auth)
    {
        $this->updateActiveTime($auth->getUsername(), time());
    }

    // custom support methods not in the interface
    protected function isLegit($input) { ... }

    protected function updateLoginTime($time) { ... }

    protected function updateActiveTime($time) { ... }

    protected function updateLogoutTime($time) { ... }
}
?>
```

You can then pass an instance of the custom adapter when creating services through the _AuthFactory_ methods:

```php
<?php
$custom_adapter = new CustomAdapter;
$login_service = $auth_factory->newLoginService($custom_adapter);
$logout_service = $auth_factory->newLogoutService($custom_adapter);
$resume_service = $auth_factory->newResumeService($custom_adapter);
?>
```

## OAuth 2.0 Adapter

OAuth 2.0 has its own chapter — see [OAuth 2.0](oauth.md).

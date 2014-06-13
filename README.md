# Aura.Auth

Provides authentication functionality and session tracking using various adapters; currently supported adapters are:

- Apache htpasswd files
- SQL tables via [PDO](http://php.net/pdo)

Note that the purpose of this package is only to authenticate user credentials; it does not currently, and probably will not in the future, handle user account creation and management. That is more properly the domain of application-level functionality, or at least a separate Aura bundle.

## Foreword

### Installation

This library requires PHP 5.3 or later, and has no userland dependencies.

> NOT YET: It is installable and autoloadable via Composer as [aura/auth](https://packagist.org/packages/aura/auth).

Alternatively, [download a release](https://github.com/auraphp/Aura.Auth/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/auraphp/Aura.Auth/badges/quality-score.png?b=develop-2)](https://scrutinizer-ci.com/g/auraphp/Aura.Auth/?branch=develop-2)
[![Code Coverage](https://scrutinizer-ci.com/g/auraphp/Aura.Auth/badges/coverage.png?b=develop-2)](https://scrutinizer-ci.com/g/auraphp/Aura.Auth/?branch=develop-2)
[![Build Status](https://travis-ci.org/auraphp/Aura.Auth.png?branch=develop-2)](https://travis-ci.org/auraphp/Aura.Auth)

To run the [PHPUnit][] tests at the command line, go to the _tests_ directory and issue `phpunit`.

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PHPUnit]: http://phpunit.de/manual/
[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md


### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.


## Getting Started

Because this package is still in very early development, THE FOLLOWING DOCUMENTATION IS INCOMPLETE AND SOMETIMES WRONG.

### Authentication State Tracking

To track the user authentication state and related information, create an _Auth_ object using the _AuthFactory_.  (The _AuthFactory_ needs a copy of the `$_COOKIES` superglobal to track session continuation.)

```php
<?php
$auth_factory = new \Aura\Auth\AuthFactory($_COOKIES);
$auth = $auth_factory->newInstance();
?>
```

You can now use the _Auth_ object to discover authentication information:

#### `getStatus()`

The `$auth->getStatus()` returns one of these constants:

- `Auth::ANON` -- the user is currently anonymous/unauthenticated.

- `Auth::IDLE` -- the user has been idle for too long, and has become

### Forcing Login And Logout


### Login and Logout Adapters

#### Via Htpasswd

To create an _Auth_ object that uses the _HtpasswdAdapter_, call the `newHtpasswdInstace()` method and pass the file path of the Apache htpasswd file.

```
<?php
$auth = $auth_factory->newHtpasswdInstance('/path/to/accounts.htpasswd');
?>
```

This will automatically use the _HtpasswdVerifier_ to check DES, MD5, and SHA passwords from the htpasswd file on a per-user basis.


#### Via PDO Connection

To create an _Auth_ object that uses the _PdoAdapter_, call the `newPdoInstace()` method and pass these parameters in order:

- a _PDO_ connection instance

- a specification to indicate how passwords are hashed in the database:

    - if a string, it is treated as a `hash()` algorithm for a _HashVerifier_ instance

    - if a `PASSWORD_*` constant from PHP 5.5 and up, it is treated as `password_hash()` algorithm for a _PasswordVerifier_ instance

    - otherwise, it is expected to be an implementation of _VerifierInterface_

- an array of column names: the first element is the username column, the second element is the hashed-password column, and additional columns are used as extra user information to be selected and returned from the database

- a `FROM` specification string to indicate one or more table names, with any other `JOIN` clauses you wish to add

- an optional `WHERE` condition string; use this to add extra conditions to the `SELECT` statement built by the adapter

Here is a straightforward example where passwords are MD5 hashed in an accounts table:

```
<?php
$pdo = new \PDO(...);
$hash = 'md5';
$cols = ('username', 'md5password');
$from = 'accounts';
$auth = $auth_factory->newPdoInstance($pdo, $hash, $cols, $from);
?>
```

Here is a more complex example that uses bcrypt instead of md5, retrieves extra user information columns from joined tables, and filters for active accounts:

```
<?php
$pdo = new \PDO(...);
$hash = PASSWORD_BCRYPT;
$cols = array(
    'accounts.username', // "AS username" is added by the adapter
    'accounts.bcryptpass', // "AS password" is added by the adapter
    'accounts.uid AS uid',
    'userinfo.email AS email',
    'userinfo.uri AS website',
    'userinfo.fullname AS display_name',
);
$from = 'accounts JOIN userinfo ON accounts.uid = userinfo.uid';
$where = 'accounts.active = 1';
$auth = $auth_factory->newPdoInstance($pdo, $hash, $cols, $from);
?>
```

(The additional information columns will be retained in the session data after successful authentication.)

### Custom Session Integration

TBD


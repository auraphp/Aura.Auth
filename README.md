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

### Instantiation

To track the user authentication state and related information, create an _Auth_ object using the _AuthFactory_.

```php
<?php
$auth_factory = new \Aura\Auth\AuthFactory($_COOKIES);
$auth = $auth_factory->newInstance();
?>
```

Creating the _Auth_ object has the side effect of resuming a previous session, if one exists; this is why it needs a copy of `$_COOKIES`. It does so through the _Session_. If a previous session was resumed, the _Auth_ object will refresh the authentication status as needed to mark the session as idle or expired. If no previous session exists, creating the _Auth_ object will not start a new one. (Please see the [session management](#session-management) section for more about session handling.)

### Forcing Login

You can force the _Auth_ object to recognize the user as authenticated by calling the `forceLogin()` method with a user name and optional arbitrary user information.

```php
<?php
$user = 'bolivar';
$info = array(
    'first_name' => 'Bolivar',
    'last_name' => 'Shagnasty',
    'email' => 'boshag@example.com',
);
$auth->forceLogin($user, $info);
?>
```

Using `forceLogin()` has the side effect of starting a new session through the _Session_ if one has not already been started, and of regenerating the session ID. (Please see the [session management](#session-management) section for more about session handling.)

The user name and user information will then be stored in the session, along with an authentication status of `Status::VALID`.

Note that `forceLogin()` does not check any credential sources. You as the application owner are telling the _Auth_ object to treat the user as authenticated.

### Forcing Logout

You can force the _Auth_ object to dismiss the existing authenticated user back to anonymity by calling the `forceLogout()` method.

```php
<?php
$auth->forceLogout();
?>
```
This clears any existing user name and user information from the session, regenerates the session ID, and sets the authentication status to `Status::ANON`. It does **not** destroy the session. (Please see the [session management](#session-management) section for more about session handling.)

Note that `forceLogout()` does not check any credential sources. You as the application owner are telling the _Auth_ object to dismiss the user as anonymous.

### Getting Authentication Information

At any time, you can retrieve authentication information using the following methods:

- `getStatus()`: returns the current authentication status constant. These constants are:

    - `Status::ANON` -- the user is currently anonymous (unauthenticated).

    - `Status::IDLE` -- the authenticated user has been idle for too long, and has become invalid. However, the _Auth_ object does not automatically log the user out, so the previous authentication information is still available.

    - `Status::EXPIRED` -- the authenticated session has lasted too long, making the user invalid. However, the _Auth_ object does not automatically log the user out, so the previous authentication information is still available.

    - `Status::VALID` -- The user is currently authenticated and is valid.

- `isValid()`, `isAnon()`, `isIdle()`, `isExpired()`: these return true or false, based on the current authentication status.

- `getName()`: returns the authenticated username string

- `getData()`: returns the array of optional arbitrary user data


### Login and Logout Handlers

TBD

#### Htpasswd Adapter

To create an adapter for Apache htpasswd files, call the `$adapter_factory->newHtpasswdInstace()` method and pass the file path of the Apache htpasswd file.

```
<?php
$auth = $auth_factory->newHtpasswdInstance('/path/to/accounts.htpasswd');
?>
```

This will automatically use the _HtpasswdVerifier_ to check DES, MD5, and SHA passwords from the htpasswd file on a per-user basis.


#### PDO Adapter

To create an adapter for PDO connections to SQL tables, call the `newPdoInstace()` method and pass these parameters in order:

- a _PDO_ connection instance

- a specification to indicate how passwords are hashed in the database:

    - if a `PASSWORD_*` constant from PHP 5.5 and up, it is treated as `password_hash()` algorithm for a _PasswordVerifier_ instance (this is the preferred method)

    - if a string, it is treated as a `hash()` algorithm for a _HashVerifier_ instance

    - otherwise, it is expected to be an implementation of _VerifierInterface_

- an array of column names: the first element is the username column, the second element is the hashed-password column, and additional columns are used as extra user information to be selected and returned from the database

- a `FROM` specification string to indicate one or more table names, with any other `JOIN` clauses you wish to add

- an optional `WHERE` condition string; use this to add extra conditions to the `SELECT` statement built by the adapter

Here is a legacy example where passwords are MD5 hashed in an accounts table:

```
<?php
$pdo = new \PDO(...);
$hash = 'md5';
$cols = ('username', 'md5password');
$from = 'accounts';
$auth = $auth_factory->newPdoInstance($pdo, $hash, $cols, $from);
?>
```

Here is a modern, more complex example that uses bcrypt instead of md5, retrieves extra user information columns from joined tables, and filters for active accounts:

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
$from = 'accounts JOIN profiles ON accounts.uid = profiles.uid';
$where = 'accounts.active = 1';
$auth = $auth_factory->newPdoInstance($pdo, $hash, $cols, $from);
?>
```

(The additional information columns will be retained in the session data after successful authentication.)

### Session Management

TBD


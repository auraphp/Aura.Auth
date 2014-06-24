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

To track authentication state and related information, create an _Auth_ object using the _AuthFactory_.

```php
<?php
$auth_factory = new \Aura\Auth\AuthFactory;
$auth = $auth_factory->newInstance();
?>
```

You can retrieve authentication information using the following methods on the _Auth_ instance:

- `getUserName()`: returns the authenticated username string

- `getUserData()`: returns the array of optional arbitrary user data

- `getFirstActive()`: returns the Unix time of first activity (login)

- `getLastActive()`: return the Unix time of most-recent activity (generally that of the current request)

- `getStatus()`: returns the current authentication status constant. These constants are:

    - `Status::ANON` -- anonymous/unauthenticated

    - `Status::IDLE` -- the authenticated session has been idle for too long

    - `Status::EXPIRED` -- the authenticated session has lasted for too long in total

    - `Status::VALID` -- authenticated and valid

- `isAnon()`, `isIdle()`, `isExpired()`, `isValid()`: these return true or false, based on the current authentication status.

You can also use the `set*()` variations of the `get*()` methods above to force the _Auth_ object to whatever values you like. However, because the values are stored in a `$_SESSION` segment, the values will not be retained if a session is not running.

To retain values in a session, you can start a session by force with `session_start()` on your own. Alternatively, it would be better to use one of the Aura.Auth package services to handle authentication and session-state management for you.


### Services

This package comes with three services for dealing with authentication phases:

- _LoginService_ to log in and start a new session,

- _LogoutService_ to log out and destroy an existing session,

- _ResumeService_ to resume a previously-started session.

You can create each by using the _AuthFactory_.  For now, we will look at how to force login and logout; later, we will show how to have the service use a credential adapter.


#### Forcing Login

You can force the _Auth_ object to a logged-in state by calling the _LoginService_ `forceLogin()` method with a user name and optional arbitrary user data.

```php
<?php
// the authentication status is currently anonymous
echo $auth->getStatus(); // ANON

// create the login service
$login_service = $auth_factory->newLoginService();

// use the service to force $auth to a logged-in state
$username = 'boshag';
$userdata = array(
    'first_name' => 'Bolivar',
    'last_name' => 'Shagnasty',
    'email' => 'boshag@example.com',
);
$login_service->forceLogin($auth, $username, $userdata);

// now the authentication status is valid
echo $auth->getStatus(); // VALID
?>
```

Using `forceLogin()` has these side effects:

- it starts a new session if one has not already been started, or resumes a previous session if one exists

- it regenerates the session ID.

> N.b.: Please see the [session management](#session-management) section for more about session management.

The specified user name and user data will be stored in a `$_SESSION` segment, along with an authentication status of `Status::VALID`.

Note that `forceLogin()` does not check any credential sources. You as the application owner are forcing the _Auth_ object to a logged-in state.


#### Forcing Logout

You can force the _Auth_ object to a logged-out state by calling the _LogoutService_ `forceLogout()` method.

```php
<?php
// the authentication status is currently valid
echo $auth->getStatus(); // VALID

// create the logout service
$logout_service = $auth_factory->newLogoutService();

// use the service to force $auth to a logged-out state
$logout_service->forceLogout();

// now the authentication status is anonymous/invalid
echo $auth->getStatus(); // ANON
?>
```

Using `forceLogout()` has these side effects:

- it clears any existing user name and user data from the `$_SESSION` segment

- it regenerates the session ID

- it destroys the session

> N.b.: Please see the [session management](#session-management) section for more about session management.

Note that `forceLogout()` does not check any credential sources. You as the application owner are forcing the _Auth_ object to a logged-out state.

#### Resuming A Session

When a PHP request ends, PHP saves the `$_SESSION` data for you. However, on the next request, PHP does not automatically start a new session for you, so `$_SESSION` is not automatically available.

You could start a new session yourself to repopulate `$_SESSION`, but that will incur a performance overhead if you don't actually need the session data.  Similarly, there may be no need to start a session when there was no session previously (and thus no data to repopulate into `$_SESSION`).  What we need is a way to start a session if one was started previously, but avoid starting a session if none was started previously.

The _ResumeService_ exists to address this problem. When you call the `resume()` method on the _ResumeService_, it examines `$_COOKIE` to see if a session cookie is present:

- If the cookie is not present, it will not start a session, and return to the calling code. This avoids starting a session when there is no `$_SESSION` data to be populated.

- If the cookie *is* present, the _ResumeService_ will start a session, thereby repopulating `$_SESSION`. Then it will update the authentication status depending on how long the session has been in place:

    - If the session has been idle for too long (i.e., too much time has passed since the last request), the _ResumeService_ will log the user out automatically and return to the calling code.

    - If the session session has expired (i.e., the total logged-in time has been too long), the _ResumeService_ will likewise log the user out automatically and return to the calling code.

    - Otherwise, the _ResumeService_ will update the last-active time on the _Auth_ object and return to the calling code.

Generally, you will want to invoke the _ResumeService_ at the beginning of your application cycle, so that the session data becomes available at the earliest opportunity.

```php
<?php
// create the resume service
$resume_service = $auth_factory->newResumeService();

// use the service to resume any previously-existing session
$resume_service->resume($auth);

// $_SESSION has now been repopulated, if a session was started previously,
// meaning the $auth object is now populated with its previous values, if any
?>
```

### Adapters

Forcing the _Auth_ object to a particular state is fine for when you want to exercise manual control over the authentication status, user name, user data, and other information. However, it is more often the case that you will want to check user credential input (username and password) against a credential store.  This is where the _Adapter_ classes come in.

To use an _Adapter_ with a _Service_, you first need to create the _Adapter_, then pass it to the _AuthFactory_ `new*Service()` method.

#### Htpasswd Adapter

##### Instantiation

To create an adapter for Apache htpasswd files, call the _AuthFactory_ `newHtpasswdAdapter()` method and pass the file path of the Apache htpasswd file.

```php
<?php
$htpasswd_adapter = $auth_factory->newHtpasswdAdapter(
    '/path/to/accounts.htpasswd'
);
?>
```

This will automatically use the _HtpasswdVerifier_ to check DES, MD5, and SHA passwords from the htpasswd file on a per-user basis.

##### Service Integration

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

For more on _LoginService_ idioms, please see the [Login Service Idioms](#login-service-idioms) section.

The _LogoutService_ and _ResumeService_ do not need credential information.

#### PDO Adapter

##### Instantiation

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

```
<?php
$pdo = new \PDO(...);
$hash = 'md5';
$cols = ('username', 'md5password');
$from = 'accounts';
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from);
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
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from);
?>
```

(The additional information columns will be retained in the session data after successful authentication.)

##### Service Integration

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

For more on _LoginService_ idioms, please see the [Login Service Idioms](#login-service-idioms) section.

The _LogoutService_ and _ResumeService_ do not need credential information.

### Login Service Idioms

TBD (this is how to build a login service)

### Session Management

TBD


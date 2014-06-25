# Aura.Auth

Provides authentication functionality and session tracking using various adapters; currently supported adapters are:

- Apache htpasswd files
- SQL tables via [PDO](http://php.net/pdo)

Note that the purpose of this package is only to authenticate user credentials; it does not currently, and probably will not in the future, handle user account creation and management. That is more properly the domain of application-level functionality, or at least a separate Aura bundle.

## Foreword

This package is still in development and not yet complete. Please review the [TODO](TODO.md) document for more information.

### Installation

This library requires PHP 5.3 or later, and has no userland dependencies. (For the newer, more-secure [`password_hash()`](http://php.net/password_hash) functionality, this library requires PHP 5.5 or later, or an alternative userland implementation such as [ircmaxell/password-compat](https://github.com/ircmaxell/password_compat).)

It is installable and autoloadable via Composer as [aura/auth](https://packagist.org/packages/aura/auth).

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

Warning: this package is still in its early development stages, so the behaviors described below may change rapidly.

### Instantiation

To track authentication state and related information, create an _Auth_ object using the _AuthFactory_.

```php
<?php
$auth_factory = new \Aura\Auth\AuthFactory($_COOKIE);
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

- it regenerates the session ID

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

For more on _LoginService_ idioms, please see the [Service Idioms](#service-idioms) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

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

```php
<?php
$pdo = new \PDO(...);
$hash = 'md5';
$cols = ('username', 'md5password');
$from = 'accounts';
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from);
?>
```

Here is a modern, more complex example that uses bcrypt instead of md5, retrieves extra user information columns from joined tables, and filters for active accounts:

```php
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

For more on _LoginService_ idioms, please see the [Service Idioms](#service-idioms) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

### Service Idioms

#### Resuming A Session

This is an example of the code needed to resume a pre-existing session. Note that the `echo` statements are intended to explain the different resulting states of the `resume()` call, and may be replaced by whatever logic you feel is appropriate. For example, you may wish to redirect to a login page when a session has idled or expired.

```php
<?php
$auth = $auth_factory->newInstance();

$resume_service = $auth_factory->newResumeService(...);
$resume_service->resume($auth);

switch (true) {
    case $auth->isAnon():
        echo "You are not logged in.";
        break;
    case $auth->isIdle():
        echo "Your session was idle for too long. Please log in again.";
        break;
    case $auth->isExpired():
        echo "Your session has expired. Please log in again.";
        break;
    case $auth->isValid():
        echo "You are still logged in.";
        break;
    default:
        echo "You have an unknown status.";
        break;
}
?>
```

> N.b.: Instead of creating the  _Auth_ and _ResumeService_ objects by hand, you may wish to use a dependency injection container such as [Aura.Di](https://github.com/auraphp/Aura.Di) to retain them for shared use throughout your application.

#### Logging In

This is an example of the code needed to effect a login. Note that the `echo` statements are intended to explain the different resulting states of the `login()` call, and may be replaced by whatever logic you feel is appropriate.

```php
<?php
$auth = $auth_factory->newInstance();

$login_service = $auth_factory->newLoginService(...);

try {
    $login_service->login($auth, $_POST);
    echo "You are now logged into a new session.";
} catch (\Aura\Auth\Exception\UsernameMissing $e) {
    echo "The 'username' field is missing or empty.";
} catch (\Aura\Auth\Exception\PasswordMissing $e) {
    echo "The 'password' field is missing or empty.";
} catch (\Aura\Auth\Exception\UsernameNotFound $e) {
    echo "The username you entered was not found.";
} catch (\Aura\Auth\Exception\MultipleMatches $e) {
    echo "There is more than one account with that username.";
} catch (\Aura\Auth\Exception\PasswordIncorrect $e) {
    echo "The password you entered was incorrect.";
}
?>
```

> N.b.: Instead of creating the  _Auth_ and _LoginService_ objects by hand, you may wish to use a dependency injection container such as [Aura.Di](https://github.com/auraphp/Aura.Di) to retain them for shared use throughout your application.


#### Logging Out

This is an example of the code needed to effect a login. Note that the `echo` statements are intended to explain the different resulting states of the `logout()` call, and may be replaced by whatever logic you feel is appropriate.

```php
<?php
$auth = $auth_factory->newInstance();

$logout_service = $auth_factory->newLogoutService(...);

$logout_service->logout($auth);

if ($auth->isAnon()) {
    echo "You are now logged out.";
} else {
    echo "Something went wrong; you are still logged in.";
}
?>
```

> N.b.: Instead of creating the  _Auth_ and _LogoutService_ objects by hand, you may wish to use a dependency injection container such as [Aura.Di](https://github.com/auraphp/Aura.Di) to retain them for shared use throughout your application.

### Custom Adapters

Although this package comes with multiple _Adapter_ classes, it may be that none of them fit your needs.

You may wish to extend one of the existing adapters to add login/logout/resume behaviors. Alternatively, you can create an _Adapter_ of your own by implementing the _AdapterInterface_ on a class of your choosing:

```php
<?php
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;

class CustomAdapter implements AdapterInterface
{
    // AdapterInterface::login()
    public function login(array $cred)
    {
        if ($this->isLegit($cred)) {
            $username = ...;
            $userdata = array(...);
            $this->updateLoginTime(time());
            return array($username, $userdata);
        } else {
            throw CustomException('Something went wrong.');
        }
    }

    // AdapterInterface::logout()
    public function logout(Auth $auth)
    {
        $this->updateLogoutTime($auth->getUsername(), time());
    }

    // AdapterInterface::resume()
    public function resume(Auth $auth)
    {
        $this->updateActiveTime($auth->getUsername(), time());
    }

    // custom support methods not in the interface
    protected function isLegit($credentials) { ... }

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

### Session Management

The _Service_ objects use a _Session_ object to start sessions, destroy them, and regenerate session IDs. The _Session_ object uses the native PHP `session_*()` functions to manage sessions.

#### Custom Sessions

If you wish to use an alternative means of managing sessions, implement the _SessionInterface_ on an object of your choice. One way to do this is by by wrapping a framework-specific session object and proxying the _SessionInterface_ methods to the wrapped object:

```php
<?php
use Aura\Auth\Session\SessionInterface;

class CustomSession implements SessionInterface
{
    protected $fwsession;

    public function __construct(FrameworkSession $fwsession)
    {
        $this->fwsession = $fwsession;
    }

    public function start()
    {
        return $this->fwsession->startSession();
    }

    public function resume()
    {
        if ($this->fwsession->isAlreadyStarted()) {
            return true;
        }

        if ($this->fwsession->canBeRestarted()) {
            return $this->fwsession->restartSession();
        }

        return false;
    }

    public function regenerateId()
    {
        return $this->fwsession->regenerateSessionId();
    }

    public function destroy()
    {
        return $this->fwsession->destroySession();
    }
}
?>
```

Then pass that custom session object to the _AuthFactory_ instantiation:

```php
<?php
use Aura\Auth\AuthFactory;

$custom_session = new CustomSession(new FrameworkSession);
$auth_factory = new AuthFactory($_COOKIE, $custom_session);
?>
```

The factory will pass your custom session object wherever it is needed.

#### Working Without Sessions

In some situations, such as with APIs where credentials are provided with every request, it may be beneficial to avoid sessions altogether. In this case, pass a _NullSession_ and _NullSegment_ to the _AuthFactory_:

```php
<?php
use Aura\Auth\AuthFactory;
use Aura\Auth\Session\NullSession;
use Aura\Auth\Session\NullSegment;

$null_session = new NullSession;
$null_segment = new NullSegment;
$auth_factory = new AuthFactory($_COOKIE, $null_session, $null_segment);
?>
```

With the _NullSession_, a session will never actually be started or destroyed, and no session ID will be created or regenerated. Likewise, no session will ever be resumed, because it will never have been saved at the end of the previous request. Finally, PHP will never create a session cookie to send in the response.

Likewise, the _NullSegment_ retains authentication information in an object property instead of in a `$_SESSION` segment. Unlike the normal _Segment_, which only retains data when `$_SESSION` is present, the _NullSegment_ will always retain data that is set into it. When the request is over, all authentication information retained in the _NullSegment_ will disappear.

When using the _NullSession_ and _NullSegment_, you will have to check  credentials via the _LoginService_ `login()` or `forceLogin()` method on each request, which in turn will retain the authentication information in the _Segment_. In an API situation this is often preferable to managing an ongoing session.

> N.b. In an API situation, the credentials may be an API token, or passed as HTTP basic or digest authentication headers.  Pass these to the adapter of your choice.


### Custom Services

You are not restricted to the login, logout, and resume services provided by this package. However, if you build a service of your own, or if you extend one of the provided services, you will have to instantiate that customized service object manually, instead of using the _AuthFactory_. This can be tedious but is not difficult, especially when using a dependency injection container system such as [Aura.Di](https://github.com/auraphp/Aura.Auth).

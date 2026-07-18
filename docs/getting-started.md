# Getting Started

## Instantiation

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

    - `Status::REMEMBERED` -- re-authenticated from a "remember me" cookie without presenting credentials this session (see [Remember Me](remember-me.md))

- `isAnon()`, `isIdle()`, `isExpired()`, `isValid()`, `isRemembered()`: these return true or false, based on the current authentication status.

You can also use the `set*()` variations of the `get*()` methods above to force the _Auth_ object to whatever values you like. However, because the values are stored in a `$_SESSION` segment, the values will not be retained if a session is not running.

To retain values in a session, you can start a session by force with `session_start()` on your own. Alternatively, it would be better to use one of the Aura.Auth package services to handle authentication and session-state management for you.


## Services

This package comes with three services for dealing with authentication phases:

- _LoginService_ to log in and start (or resume) a session,

- _LogoutService_ to log out and remove the username and user data in the session (note that this **does not** destroy the session), and

- _ResumeService_ to resume a previously-started session.

You can create each by using the _AuthFactory_.  For now, we will look at how to force login and logout; later, we will show how to have the service use a credential adapter.


### Forcing Login

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


### Forcing Logout

You can force the _Auth_ object to a logged-out state by calling the _LogoutService_ `forceLogout()` method.

```php
<?php
// the authentication status is currently valid
echo $auth->getStatus(); // VALID

// create the logout service
$logout_service = $auth_factory->newLogoutService();

// use the service to force $auth to a logged-out state
$logout_service->forceLogout($auth);

// now the authentication status is anonymous/invalid
echo $auth->getStatus(); // ANON
?>
```

Using `forceLogout()` has these side effects:

- it clears any existing username and user data from the `$_SESSION` segment

- it regenerates the session ID

Note that `forceLogout()` does not check any credential sources. You as the application owner are forcing the _Auth_ object to a logged-out state.

Note also that this **does not** destroy the session. This is because you may have other things you need in the session memory, such as flash messages.


### Resuming A Session

When a PHP request ends, PHP saves the `$_SESSION` data for you. However, on the next request, PHP does not automatically start a new session for you, so `$_SESSION` is not automatically available.

You could start a new session yourself to repopulate `$_SESSION`, but that will incur a performance overhead if you don't actually need the session data.  Similarly, there may be no need to start a session when there was no session previously (and thus no data to repopulate into `$_SESSION`).  What we need is a way to start a session if one was started previously, but avoid starting a session if none was started previously.

The _ResumeService_ exists to address this problem. When you call the `resume()` method on the _ResumeService_, it examines `$_COOKIE` to see if a session cookie is present:

- If the cookie is not present, it will not start a session, and return to the calling code. This avoids starting a session when there is no `$_SESSION` data to be populated.

- If the cookie *is* present, the _ResumeService_ will start a session, thereby repopulating `$_SESSION`. Then it will update the authentication status depending on how long the session has been in place:

    - If the session has been idle for too long (i.e., too much time has passed since the last request), the _ResumeService_ will log the user out automatically and return to the calling code.

    - If the session has expired (i.e., the total logged-in time has been too long), the _ResumeService_ will likewise log the user out automatically and return to the calling code.

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

Next, see how to check credentials against a store using [Adapters](adapters.md).

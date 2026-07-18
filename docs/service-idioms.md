# Service Idioms

## Resuming A Session

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

## Logging In

This is an example of the code needed to effect a login. Note that the `echo` and `$log` statements are intended to explain the different resulting states of the `login()` call, and may be replaced by whatever logic you feel is appropriate; in particular, you should probably not expose the exact nature of the failure, to help mitigate brute-force attempts.

```php
<?php

class InvalidLoginException extends Exception {}

$auth = $auth_factory->newInstance();

$login_service = $auth_factory->newLoginService(...);

try {

    $login_service->login($auth, array(
        'username' => $_POST['username'],
        'password' => $_POST['password'],
    );
    echo "You are now logged into a new session.";

} catch (\Aura\Auth\Exception\UsernameMissing $e) {

    $log->notice("The 'username' field is missing or empty.");
    throw new InvalidLoginException();

} catch (\Aura\Auth\Exception\PasswordMissing $e) {

    $log->notice("The 'password' field is missing or empty.");
    throw new InvalidLoginException();

} catch (\Aura\Auth\Exception\UsernameNotFound $e) {

    $log->warning("The username you entered was not found.");
    throw new InvalidLoginException();

} catch (\Aura\Auth\Exception\MultipleMatches $e) {

    $log->warning("There is more than one account with that username.");
    throw new InvalidLoginException();

} catch (\Aura\Auth\Exception\PasswordIncorrect $e) {

    $log->notice("The password you entered was incorrect.");
    throw new InvalidLoginException();

} catch (\Aura\Auth\Exception\ConnectionFailed $e) {

    $log->notice("Cound not connect to IMAP or LDAP server.");
    $log->info("This could be because the username or password was wrong,");
    $log->info("or because the the connect operation itself failed in some way. ");
    $log->info($e->getMessage());
    throw new InvalidLoginException();

} catch (\Aura\Auth\Exception\BindFailed $e) {

    $log->notice("Cound not bind to LDAP server.");
    $log->info("This could be because the username or password was wrong,");
    $log->info("or because the the bind operation itself failed in some way. ");
    $log->info($e->getMessage());
    throw new InvalidLoginException();

} catch (InvalidLoginException $e) {

    echo "Invalid login details. Please try again.";

}
?>
```

> N.b.: Instead of creating the  _Auth_ and _LoginService_ objects by hand, you may wish to use a dependency injection container such as [Aura.Di](https://github.com/auraphp/Aura.Di) to retain them for shared use throughout your application.

Alternatively, you may wish to use credentials from the HTTP `Authorization: Basic` headers instead of using `$_POST` or other form-related inputs.  On Apache `mod_php` you might use the auto-populated `$_SERVER['PHP_AUTH_*']` values:

```php
<?php
$authorization_basic = function () {
    return array(
        isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null,
        isset($_SERVER['PHP_AUTH_PW'])   ? $_SERVER['PHP_AUTH_PW']   : null,
    );
}

list($username, $password) = $authorization_basic();
$login_service->login($auth, array(
    'username' => $username,
    'password' => $password,
));
?>
```

On other servers you may need to extract the credentials from the `Authorization: Basic` header itself:

```php
<?php
$authorization_basic = function () {

    $header = isset($_SERVER['HTTP_AUTHORIZATION'])
            ? $_SERVER['HTTP_AUTHORIZATION']
            : '';

    if (strtolower(substr($header, 0, 6)) !== 'basic ') {
        return array(null, null);
    }

    $encoded = substr($header, 6);
    $decoded = base64_decode($encoded);
    return explode(':', $decoded);

}

list($username, $password) = $authorization_basic();
$login_service->login($auth, array(
    'username' => $username,
    'password' => $password,
));
?>
```

To keep a user logged in across browser sessions, pass a truthy `remember` value in the login input; see [Remember Me](remember-me.md).

## Logging Out

This is an example of the code needed to effect a logout. Note that the `echo` statements are intended to explain the different resulting states of the `logout()` call, and may be replaced by whatever logic you feel is appropriate.

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

## Custom Services

You are not restricted to the login, logout, and resume services provided by this package. However, if you build a service of your own, or if you extend one of the provided services, you will have to instantiate that customized service object manually, instead of using the _AuthFactory_. This can be tedious but is not difficult, especially when using a dependency injection container system such as [Aura.Di](https://github.com/auraphp/Aura.Di).

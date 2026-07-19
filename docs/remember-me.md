# Remember Me

The "remember me" feature keeps a user authenticated across browser sessions by
issuing a long-lived cookie that re-establishes the session on a later visit,
without the user re-entering credentials.

It uses the [split-token](https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence)
scheme: the cookie holds a public `selector` and a secret `validator`, while the
server stores only the `selector` and a **SHA-256 hash** of the validator. The
validator is compared in constant time, and a **new validator is issued on every
use** (rotation), so a stolen cookie is single-use and a leak of the storage
backend does not by itself yield usable cookies.

## The REMEMBERED Status

A user resumed from a remember-me cookie is given the `Status::REMEMBERED`
status rather than `Status::VALID`. Such a user is authenticated but **did not
present credentials this session**, so you should treat them as lower privilege:
require a fresh login before allowing password changes, administrative actions,
or other sensitive operations. Check it with `$auth->isRemembered()`:

```php
<?php
if ($auth->isRemembered()) {
    // authenticated from a cookie; block sensitive actions and prompt
    // for the password to "step up" to a full (VALID) session.
}
?>
```

A successful credentialed `login()` upgrades the user from `REMEMBERED` to
`VALID`.

## Token Storage

Remember-me tokens are kept in a pluggable, server-side store implementing
`Aura\Auth\Remember\RememberStorageInterface`. A PDO-backed implementation is
provided; you can supply your own (Redis, Memcached, a file store, etc.) by
implementing the interface.

For the PDO store, create a table (adjust the column types to your database):

```sql
CREATE TABLE aura_auth_remember (
    selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
    hashed_validator VARCHAR(64)  NOT NULL,
    username         VARCHAR(255) NOT NULL,
    userdata         TEXT         NULL,
    expires          INTEGER      NOT NULL
);
CREATE INDEX aura_auth_remember_username ON aura_auth_remember (username);
```

## Wiring The Service

Build a _RememberService_ from the factory and pass it into the login, logout,
and resume services. Because the remember service is an **optional** dependency
of those services, existing code that does not use it is unaffected.

```php
<?php
$storage = $auth_factory->newPdoRememberStorage($pdo);

$remember_service = $auth_factory->newRememberService($storage, array(
    'name'     => 'remember',   // cookie name
    'ttl'      => 2592000,      // token lifetime in seconds (default 30 days)
    'secure'   => true,         // only send over HTTPS (default true)
    'httponly' => true,         // hide from JavaScript (default true)
    'samesite' => 'Lax',        // SameSite policy (default 'Lax')
    'path'     => '/',
    'domain'   => '',
    // 'user_loader' => $fn,    // optional; see "Refreshing User Data" below
));

$login_service  = $auth_factory->newLoginService($adapter, $remember_service);
$logout_service = $auth_factory->newLogoutService($adapter, $remember_service);
$resume_service = $auth_factory->newResumeService($adapter, 3600, 86400, $remember_service);
?>
```

Once wired, the feature works through the call sites you already have:

- **Login.** Pass a truthy `remember` value in the login input (for example, from
  a "Remember me" checkbox). On success, a token is issued and the cookie is set:

  ```php
  <?php
  $login_service->login($auth, array(
      'username' => $_POST['username'],
      'password' => $_POST['password'],
      'remember' => ! empty($_POST['remember']),
  ));
  ?>
  ```

- **Resume.** When the session is anonymous or has timed out, `resume()`
  automatically falls back to the remember-me cookie, rotating the token and
  setting the status to `REMEMBERED`:

  ```php
  <?php
  $resume_service->resume($auth);
  if ($auth->isRemembered()) {
      echo "Welcome back! (Please log in to make account changes.)";
  }
  ?>
  ```

- **Logout.** `logout()` deletes the stored token and clears the cookie, so the
  user is not silently remembered again:

  ```php
  <?php
  $logout_service->logout($auth);
  ?>
  ```

## Refreshing User Data On Resume

By default, `resume()` restores the user data **snapshot** that was captured in
storage when the token was issued. If an administrator later changes the user
(new roles, a changed email, a disabled account), a remembered session keeps
serving the stale copy until the next full credential login.

To pick up such changes, pass an optional `user_loader` callable. It receives the
remembered user name and returns the fresh user data on each resume:

```php
<?php
$remember_service = $auth_factory->newRememberService($storage, array(
    'user_loader' => function ($username) use ($pdo) {
        $stm = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stm->execute(array('username' => $username));
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        // return null to reject: the user no longer exists or is disabled,
        // which revokes the token and leaves the visitor anonymous.
        if (! $user || ! $user['is_active']) {
            return null;
        }

        // otherwise return the fresh user data to restore
        unset($user['password']);
        return $user;
    },
));
?>
```

The loader's return value is used in place of the stored snapshot; the remembered
username is preserved as the identity. Returning `null` treats the user as gone:
the token is deleted from storage, the cookie is cleared, and `resume()` returns
`false` (exactly like an expired or tampered token).

## Log Out Everywhere

To revoke every remember-me token for a user (for example, after a password
change), delete them by user name through the storage:

```php
<?php
$storage->deleteByUsername($username);
?>
```

You should also periodically call `$storage->deleteExpired()` (for example, from
a cron task) to purge stale tokens.

## Using The Service Standalone

The _RememberService_ can also be used directly, independent of the other
services: `remember($auth)` issues a token for the current user, `resume($auth)`
re-establishes an anonymous session from the cookie, and `forget($auth)`
discards the token.

> N.b.: Rate-limiting of login attempts is a related but separate concern that
> applies to normal credential logins as well; it is not part of this feature.

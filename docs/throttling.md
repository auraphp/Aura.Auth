# Login Throttling

Login throttling slows down brute-force password guessing. It counts failed
login attempts per user name and, once a threshold is passed, makes each further
attempt wait a little longer before it is allowed — an **exponential backoff**.

Because the counter keys on the user name and applies a *delay* rather than a
hard lockout, a legitimate user who mistypes a few times is only briefly slowed,
and no account can ever be fully locked out by someone flooding it with bad
passwords.

## The Backoff Policy

The first `max_attempts` failures are free. After that, an attempt must wait

```
min(2 ^ (failures - max_attempts), cap)
```

seconds since the most recent failure. With the defaults (`max_attempts` = 5,
`cap` = 900), the delays grow like this:

| Failures | Wait before next attempt |
|---------:|--------------------------|
| ≤ 5      | none                     |
| 6        | 2s                       |
| 7        | 4s                       |
| 8        | 8s                       |
| …        | doubling…                |
| 15+      | capped at 900s (15 min)  |

A successful login clears the counter immediately.

## Failure Storage

Failure counters live in a pluggable, server-side store implementing
`Aura\Auth\Throttle\ThrottleStorageInterface`. Two implementations ship with the
library — PDO and Redis — and you can supply your own (Memcached, DynamoDB, etc.)
by implementing the interface.

Each store takes a `window` (default 900 seconds): failures older than the window
no longer count, so a counter naturally decays over time.

> N.b.: Do **not** back throttling with the session — a first-time attacker has no
> session yet, so pre-login counters must live in shared storage.

### PDO Storage

Reuses a PDO connection you already have. Create a table (adjust the column types
to your database — `throttle_key` avoids the reserved word `key`):

```sql
CREATE TABLE aura_auth_throttle (
    id           INTEGER      NOT NULL PRIMARY KEY AUTOINCREMENT,
    throttle_key VARCHAR(255) NOT NULL,
    attempted_at INTEGER      NOT NULL
);
CREATE INDEX aura_auth_throttle_key ON aura_auth_throttle (throttle_key, attempted_at);
```

(On MySQL use `AUTO_INCREMENT`; on PostgreSQL use a `SERIAL`/`BIGSERIAL` column.)

```php
<?php
$storage = $auth_factory->newPdoThrottleStorage($pdo);
// or: newPdoThrottleStorage($pdo, 'my_table', 1800)
?>
```

### Redis Storage

Redis is a natural fit: each key is a small hash with a native TTL, so expired
counters clean themselves up. The store talks to Redis through
`Aura\Auth\Throttle\RedisClientInterface`, which keeps it independent of any one
client library. The shipped `NativeRedisClient` adapts either a phpredis `\Redis`
or a `Predis\Client`:

```php
<?php
$storage = $auth_factory->newRedisThrottleStorage(new Redis());
// a Predis\Client works too:
$storage = $auth_factory->newRedisThrottleStorage(new Predis\Client());
?>
```

The `ext-redis` extension is a Composer `suggest`, not a hard requirement.

If you use a Redis client whose method names or signatures differ, implement
`RedisClientInterface` yourself and pass it straight in — the factory accepts any
implementation as-is:

```php
<?php
$client  = new MyRedisClientAdapter($someOtherRedisLibrary);
$storage = $auth_factory->newRedisThrottleStorage($client);
?>
```

## Wiring The Adapter

Throttling is applied by wrapping your existing credential adapter with a
`ThrottleAdapter`. Because every adapter shares the same interface, this works
with **any** of them — PDO, htpasswd, LDAP, IMAP, OAuth — and needs no change to
the login service:

```php
<?php
// 1. your usual credential adapter
$adapter = $auth_factory->newPdoAdapter($pdo, PASSWORD_BCRYPT, ['username', 'password'], 'users');

// 2. the throttle policy over some storage
$throttle = $auth_factory->newThrottleService($storage, array(
    'max_attempts' => 5,    // free attempts before backoff (default 5)
    'cap'          => 900,   // maximum backoff in seconds (default 15 min)
));

// 3. wrap the adapter, then build the login service from the wrapped one
$throttled = $auth_factory->newThrottleAdapter($adapter, $throttle);
$login_service = $auth_factory->newLoginService($throttled);
?>
```

That is the whole change — your login call site stays exactly the same.

## Handling A Throttled Attempt

When an attempt is blocked, `login()` throws
`Aura\Auth\Exception\ThrottleExceeded`. It is a subclass of the base
`Aura\Auth\Exception`, and it carries how many seconds the user must wait, so you
can tell them when to try again:

```php
<?php
use Aura\Auth\Exception\ThrottleExceeded;
use Aura\Auth\Exception as AuthException;

try {
    $login_service->login($auth, array(
        'username' => $_POST['username'],
        'password' => $_POST['password'],
    ));
} catch (ThrottleExceeded $e) {
    $seconds = $e->getSecondsRemaining();
    echo "Too many attempts. Please try again in {$seconds} seconds.";
} catch (AuthException $e) {
    // ordinary bad-credentials handling (UsernameNotFound, PasswordIncorrect, …)
    echo "Login failed.";
}
?>
```

Because `ThrottleExceeded` extends the base auth exception, existing `catch
(\Aura\Auth\Exception ...)` blocks keep working; catch `ThrottleExceeded` first
only if you want to show the wait time.

## Housekeeping

For the PDO store, periodically purge stale rows (for example, from a cron task):

```php
<?php
$storage->deleteExpired();
?>
```

The Redis store expires keys natively, so `deleteExpired()` there is a harmless
no-op.

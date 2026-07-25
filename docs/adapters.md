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

#### Bind, Search, Rebind

The direct bind above only works when every user's DN follows the same
`$dnformat` pattern. If your users live across multiple sub-trees (OUs), or you
want to read user attributes at login time, pass a fourth `$search` argument to
use the "bind, search, rebind" pattern: the adapter first binds with a service
account, searches for the user to discover their real DN (and attributes), then
rebinds as that DN to verify the password.

```php
<?php
$ldap_adapter = $auth_factory->newLdapAdapter(
    'ldaps://ldap.example.com:636',
    'uid=%s,ou=users,dc=example,dc=org', // ignored when $search is given
    array(
        LDAP_OPT_PROTOCOL_VERSION => 3,
        LDAP_OPT_REFERRALS => 0,
    ),
    array(
        'binddn'     => 'cn=service,dc=example,dc=org', // service account DN
        'bindpw'     => 'service-account-password',
        'basedn'     => 'dc=example,dc=org',            // where to search
        'filter'     => '(uid=%s)',                     // %s = escaped username
        'attributes' => array('cn', 'mail'),            // optional; default all
    )
);
?>
```

On success the login returns the username together with the requested
attributes (single-valued attributes as scalars, multi-valued as arrays), so
they are available on the authenticated _Auth_ user data. A search that matches
no entry throws `Aura\Auth\Exception\UsernameNotFound`; a search that matches
more than one throws `Aura\Auth\Exception\MultipleMatches`; a failed service or
user bind throws `Aura\Auth\Exception\BindFailed`.

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

    - if a `PASSWORD_*` constant, it is treated as a `password_hash()` algorithm for a _PasswordVerifier_ instance (this is the preferred method)

    - if a string, it is treated as a `hash()` algorithm for a _PasswordVerifier_ instance; see the warning under the legacy example below

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

> **Do not use a string algorithm for new applications.** Passing `'md5'`, or
> any other `hash()` algorithm name, compares a plain unsalted digest of the
> password. There is no salt and no work factor, so the stored values fall to
> rainbow tables and to hardware that computes billions of these digests per
> second. This path exists so that a site with an existing legacy password
> column can keep authenticating users while it migrates them, and for no other
> reason.
>
> To migrate, verify with the legacy algorithm and rehash on each successful
> login, as described under [Rehashing Stored Passwords](#rehashing-stored-passwords)
> below. Once the column holds only `password_hash()` output, switch the
> verifier to `new PasswordVerifier(PASSWORD_BCRYPT)`.

### Rehashing Stored Passwords

A stored hash can fall behind the policy you would apply today: it may use an
algorithm you have moved off, a bcrypt cost you have since raised, or a plain
`hash()` digest from before `password_hash()`. Existing hashes cannot be
upgraded in bulk, because a hash cannot be converted without the password. The
one moment the plaintext is available is a successful login.

After a successful `login()`, ask the adapter whether the hash it verified
against should be replaced:

```php
<?php
use Aura\Auth\Exception as AuthException;

$input = array(
    'username' => $_POST['username'],
    'password' => $_POST['password'],
);

try {
    $login_service->login($auth, $input);
} catch (AuthException $e) {
    echo "Invalid username or password.";
    return;
}

if ($pdo_adapter->needsRehash()) {
    $sth = $pdo->prepare(
        'UPDATE accounts SET password = :password WHERE username = :username'
    );
    $sth->execute(array(
        'password' => password_hash($input['password'], PASSWORD_BCRYPT),
        'username' => $auth->getUserName(),
    ));
}
?>
```

Each user is upgraded the next time they log in, and the column drains of old
hashes as the active accounts return.

`needsRehash()` describes the login that just happened on that adapter
instance, so read it immediately after `login()` returns. It is `false` after
a failed login, and `false` when the verifier cannot report -- only verifiers
implementing `Aura\Auth\Verifier\RehashInterface` can, which `PasswordVerifier`
does.

For a cost increase to be detected, the verifier has to know the cost you now
use. Pass the same options you pass to `password_hash()`:

```php
<?php
$verifier = new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 12));
?>
```

Without the options, `needsRehash()` still reports a changed algorithm and
always reports legacy `hash()` digests, but a cost-10 hash under a cost-12
policy looks current. Note that `newPdoAdapter()` builds a
`PasswordVerifier` with no options when handed a bare algorithm, so pass a
constructed verifier when you want cost tracking.

`ThrottleAdapter` passes `needsRehash()` through to the adapter it wraps, so
throttling does not interrupt migration. A custom decorator of your own needs
to forward it too, or migration will silently stop.

#### Rehashing Automatically

Checking `needsRehash()` by hand has one weakness: if the block is forgotten,
or lives on only one of several login paths, nothing breaks and nothing warns.
The migration simply never happens.

To have the adapter do it, give it a writer — an implementation of
`Aura\Auth\Rehash\RehashStorageInterface`. `PdoRehashStorage` covers a single
accounts table:

```php
<?php
$rehash_storage = $auth_factory->newPdoRehashStorage(
    $pdo,               // must be writable; a read replica fails every rehash
    'accounts',         // table
    'username',         // username column
    'password',         // password column to overwrite
    PASSWORD_BCRYPT,    // algorithm to migrate *to*
    array('cost' => 12) // options for *that* algorithm
);

$pdo_adapter->setRehashStorage($rehash_storage);
?>
```

The options belong to the algorithm being written, so they are chosen for it and
not copied from the verifier — a legacy verifier has none to copy, and a `cost`
handed to argon2id is ignored. The one case where the two do have to line up is
when both are the same algorithm: the verifier judges a hash outdated with
`password_needs_rehash()` against its *own* options, so a writer set to a weaker
cost leaves the replacement outdated too, and the row is rewritten on every
login from then on.

With that wired, a successful login whose stored hash is outdated replaces it
before returning, and `needsRehash()` is then false because there is nothing
left to do. Nothing else in the application changes.

Note that the algorithm belongs to the *writer*, not the verifier. That is what
lets a legacy column migrate: the verifier reads `'md5'`, the writer stores
bcrypt, and each account moves across the first time its owner logs in.

Accounts migrate one at a time, as their owners return, so the column holds
both kinds of hash for as long as that takes — possibly forever, for accounts
that never log in again. A `PasswordVerifier` configured with a legacy
algorithm therefore reads **both**: it uses `password_verify()` when the stored
value is `password_hash()` output and the legacy algorithm only when it is not.
Migrated users keep working while the rest are still waiting their turn, and a
migrated hash is not rewritten on every subsequent login.

Once the column holds no legacy digests — check with something like
`SELECT COUNT(*) FROM accounts WHERE password NOT LIKE '$2y$%'` — switch the
verifier to `new PasswordVerifier(PASSWORD_BCRYPT, $options)` and the remaining
stragglers will simply fail to authenticate rather than being migrated. Leaving
the legacy verifier in place indefinitely is also safe, but it keeps the ability
to accept an old digest alive for no reason.

Anything less direct than one table — a password in a joined table, a composite
key, an audit row to write alongside — wants your own implementation. The
interface is a single method:

```php
<?php
use Aura\Auth\Rehash\RehashStorageInterface;

class MyRehashStorage implements RehashStorageInterface
{
    public function rehash($username, $plaintext): void
    {
        // called only after $plaintext verified successfully for $username
    }
}
?>
```

**A failed rehash never fails the login.** Rehashing is housekeeping, and a
locked table or a revoked grant should not stop a user with correct credentials
from getting in. The adapter catches whatever the writer throws and exposes it:

```php
<?php
$login_service->login($auth, $input);

if ($error = $pdo_adapter->getRehashError()) {
    $logger->error('password rehash failed: ' . $error->getMessage());
}
?>
```

Log it. A writer that rejects every rehash means the migration is going
nowhere, and this is the only sign you will get. When a rehash fails,
`needsRehash()` stays true, so a manual block still sees the outstanding work.

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

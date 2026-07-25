# API Tokens

Cookies and `$_SESSION` are a poor fit for a REST API: sessions are stateful and
lock, and a cookie sent automatically by the browser is what makes CSRF possible
in the first place. API token authentication instead has the client send a
credential in a header on every request. Nothing is stored between requests, so
there is no session to lock and no cookie for a cross-site request to abuse.

The token is **opaque**: a random string that means nothing by itself and is
looked up server-side. Because the server owns the record, revoking a token is
simply deleting a row, and it stops working immediately.

> N.b.: JWTs are deliberately not shipped. Their one real advantage — verifying
> without touching the store — is exactly what breaks revocation, and
> deployments that need revocation end up adding a denylist, which restores the
> per-request lookup while keeping JWT's much larger validation surface (`alg:
> none`, HS/RS confusion, weak secrets, `exp`/`aud`/`iss` handling). If you need
> JWT or [PASETO](https://paseto.io/) for a federated or cross-service topology,
> verify it in your own code and use `forceLogin()`.

## How A Token Is Built

A token value is two parts joined by a colon:

```
3f2a9c81d4e6b70a:9c81d4e6b70a3f2a9c81d4e6b70a3f2a9c81d4e6b70a3f2a9c81d4e6b70a3f2a
└─── selector ──┘ └──────────────────── validator ────────────────────────────┘
```

The **selector** is a public lookup key. The **validator** is the secret; only
its SHA-256 hash is stored, and it is compared in constant time. So a leaked
database yields no usable tokens, and an attacker who knows a selector still has
nothing to present.

This is the same split-token scheme used by [remember me](remember-me.md), with
one deliberate difference: **API tokens are not rotated on use.** A remember-me
cookie rotates its validator every time so a stolen cookie works only once, but
an API token is routinely presented by several requests at once, and rotating it
would invalidate the copies still in flight. Tokens end by revocation or expiry
instead.

## Token Storage

Tokens live in a pluggable, server-side store implementing
`Aura\Auth\Token\TokenStorageInterface`. A PDO implementation ships with the
library; supply your own for anything else.

Create a table (adjust the column types to your database):

```sql
CREATE TABLE aura_auth_token (
    selector         VARCHAR(32)  NOT NULL PRIMARY KEY,
    hashed_validator VARCHAR(64)  NOT NULL,
    username         VARCHAR(255) NOT NULL,
    userdata         TEXT         NULL,
    label            VARCHAR(255) NULL,
    expires          INTEGER      NOT NULL,
    created_at       INTEGER      NOT NULL,
    last_used_at     INTEGER      NULL
);
CREATE INDEX aura_auth_token_username ON aura_auth_token (username);
```

Then build the storage and service:

```php
$auth_factory = new \Aura\Auth\AuthFactory(array());

$storage = $auth_factory->newPdoTokenStorage($pdo);
$token_service = $auth_factory->newTokenService($storage);
```

`newTokenService()` takes an options array with a `ttl` key — the default token
lifetime in seconds, 30 days if unset.

The empty array is not a placeholder. `AuthFactory` requires the cookies as its
first argument, but only two things ever read them: resuming a session, and the
remember-me cookie. Nothing on the token path touches either, so an API-only
application has nothing to hand it. Pass `$_COOKIE` when the same factory also
serves ordinary session logins — which is likely, since tokens are issued from
one, as [Issuing A Token](#issuing-a-token) shows.

## Issuing A Token

Issuing is an ordinary authenticated action: the user logs in however your
application normally logs people in, and *then* asks for a token.

```php
$token = $token_service->issue(
    $auth->getUserName(),   // who the token authenticates as
    array(),                // arbitrary data to carry with it
    'CI deploy'             // an optional human-readable label
);

echo $token; // 3f2a9c81d4e6b70a:9c81d4e6...
```

> N.b.: **Show the token to the user now.** Storage keeps only the hash of the
> validator, so this return value is the one and only chance to see it. This is
> intentional — it is the same property that makes a database leak harmless.

Pass a fourth argument to override the lifetime for one token:

```php
$token = $token_service->issue($username, array(), 'short lived', 3600);
```

## Authenticating A Request

Three objects: an `Auth` backed by an in-memory segment, a `HeaderAdapter` that
reads the token, and an `ApiResumeService` that ties them together.

```php
$auth = $auth_factory->newApiInstance();

$adapter = $auth_factory->newHeaderAdapter($token_service, $_SERVER);
$api_resume = $auth_factory->newApiResumeService($adapter);

try {
    if ($api_resume->resume($auth)) {
        // $auth->getUserName(), $auth->getUserData() are populated
    } else {
        http_response_code(401); // no usable credential
    }
} catch (\Aura\Auth\Exception\TokenExpired $e) {
    http_response_code(401);
    echo "Token expired at " . gmdate('c', $e->getExpires()) . "; issue a new one.";
}
```

Use `newApiInstance()`, **not** `newInstance()`. The latter returns an `Auth`
backed by a session segment, which silently discards its writes when no session
is active — leaving you with an anonymous `Auth` even though the token verified.
`newApiInstance()` uses an `ArraySegment` that holds the values for the duration
of the request and then forgets them, which is the whole point.

By default the adapter reads `Authorization: Bearer <token>`. The scheme is
matched case-insensitively. To use a different header:

```php
$adapter = $auth_factory->newHeaderAdapter($token_service, $_SERVER, array(
    'header' => 'HTTP_X_API_TOKEN',
    'prefix' => '',   // read the header value verbatim
));
```

> N.b.: Some server configurations drop the `Authorization` header before PHP
> sees it. On Apache with CGI/FastCGI you may need
> `CGIPassAuth On`, or an equivalent rewrite, for `$_SERVER['HTTP_AUTHORIZATION']`
> to be populated.

## What Verification Reports

`resume()` returns `false` for a request with no usable credential, and throws
only for expiry:

| Situation | Result |
|---|---|
| No token in the request | `false` |
| Malformed token value | `false` |
| Unknown selector | `false` |
| Validator does not match | `false` |
| Genuine token, past expiry | throws `Exception\TokenExpired` |

The middle three are deliberately indistinguishable. Telling an unknown selector
apart from a wrong validator would reveal whether a given selector exists, and
selectors travel in the clear as half of every token.

Expiry is different: the validator is matched *before* the expiry is examined, so
only a caller already holding the genuine token can trigger it. Reporting it
therefore leaks nothing, and lets you answer "your token expired" rather than a
flat refusal. The exception carries `getExpires()`.

A failed verification does **not** delete the stored token. Deleting on a
mismatch would let anyone who has merely seen a selector revoke somebody else's
token by presenting it with a wrong validator.

## Revoking Tokens

```php
// revoke the token in the current request (verified first)
$token_service->revoke($token_value);

// revoke from a management UI, where the user is already authenticated
$token_service->revokeBySelector($selector);

// "sign out everywhere"
$token_service->revokeAll($username);
```

`revoke()` verifies the token before deleting, so holding a selector alone is not
enough to revoke someone else's token. An expired token can still be revoked —
its validator matched, so expiry should not be the reason it cannot be cleaned
up. `revokeBySelector()` skips that check and so must never be handed a selector
straight from an incoming request.

`HeaderAdapter::logout()` revokes the request's token. There is no session to
destroy, so logging out of a token-authenticated request means the token stops
working everywhere it was copied to.

Expired rows are not removed automatically. Sweep them periodically:

```php
$token_service->deleteExpired();
```

## Tracking Last Use

Off by default. Turn it on with the third argument to the storage:

```php
$storage = $auth_factory->newPdoTokenStorage($pdo, 'aura_auth_token', true);
```

It buys "last used 3 days ago" reporting and lets you prune abandoned tokens. It
costs an `UPDATE` on every authenticated request, against the same row that
request already reads — noticeable on a busy API. The column exists either way,
so enabling it later needs no migration.

> N.b.: Concurrent requests bearing the same token race on that update, so the
> value is a rough indication rather than an audit record. If you need a real
> access trail, write a log.

## Carrying Data With A Token: Scopes

The `userdata` array is stored and handed back untouched — the library never
interprets it. That is where per-token restrictions belong:

```php
$token = $token_service->issue($username, array(
    'scopes' => array('read:builds', 'write:deploys'),
), 'CI deploy');

// later, on an authenticated request
$scopes = $auth->getUserData()['scopes'] ?? null;
```

Enforcing scopes is **authorization**, which this library does not do. Aura.Auth
answers *who are you*; deciding *what may you do* is your application's job, or
that of a permissions library.

When you do enforce them, the rule that matters is:

```
effective permission = the user's permissions ∩ the token's scopes
```

A token can only ever **narrow** what its owner may already do. It never grants
anything. So a check looks like this:

```php
function can($auth, $action) {
    // the user side: roles, ACL rules, whatever you model
    if (! userCan($auth->getUserName(), $action)) {
        return false;
    }

    // the token side: absent scopes (a session login) impose no ceiling
    $scopes = $auth->getUserData()['scopes'] ?? null;
    return $scopes === null || in_array($action, $scopes, true);
}
```

> N.b.: Checking scopes *instead of* the user's permissions, rather than in
> addition to them, is a privilege-escalation bug: a token claiming
> `scopes => ['admin:*']` would grant admin rights to a user who has none. The
> intersection is what keeps a leaked token bounded from both sides.

## Why Not `session_set_save_handler`?

A recurring suggestion is to keep sessions but store them in a database. That
solves a different problem — it changes *where session data lives*, not how the
client is identified, so you are still transporting a session ID in a cookie.

Bending it into header auth means `session_id($token_from_header)`, which is
worth avoiding: the token becomes the session ID and is therefore stored raw
rather than hashed, PHP's session GC governs its lifetime instead of your policy,
there is no row of your own to hold a label or scopes, and session locking — one
of the reasons to leave sessions behind — comes back.

## Sessions And Tokens Side By Side

Nothing stops an application serving both. Build two `Auth` trackers from the
same factory — `newInstance()` for browser traffic, `newApiInstance()` for API
traffic — and pick per route. Both produce `Status::VALID` on success, so
downstream authorization code does not care which door the user came through.

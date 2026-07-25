# Security Notes

## Account Enumeration

Account enumeration is an attacker learning *which usernames exist* without
learning any password. That alone is worth something: it confirms a person holds
an account on your site, and it narrows a credential-stuffing list down to
addresses worth attacking. A login form can give this away through three
separate channels, and closing one does nothing for the others.

Aura.Auth closes the channel it owns. You own the other two.

### Response Time (handled by the library)

Verifying a password is deliberately slow — that is what bcrypt is for. So a
naive login costs very different amounts of work depending on whether the
username was found:

- **username exists, password wrong** — a query, then a full bcrypt
  verification: on the order of 100–300ms.
- **username does not exist** — a query that matches nothing, and an immediate
  failure: on the order of 1ms.

An attacker does not need an error message to tell those apart. They submit a
list of candidate addresses with a junk password and keep the slow ones. That is
a two-hundred-fold difference, readable straight through internet jitter.

`PdoAdapter` and `HtpasswdAdapter` therefore run a throwaway verification
against a dummy hash when no account matched, so both outcomes cost about the
same before the same exception is thrown. This is not something an application
can fix from the outside, because the difference is created inside `login()`.
The network adapters are a separate case, covered in [Response Time on LDAP and
IMAP](#response-time-on-ldap-and-imap-not-closed) below.

Three things are worth knowing about how it works.

**It does not rely on the dummy hash being secret.** `AbstractAdapter` exposes
fallback hashes as public constants and this documentation describes the
mechanism exactly. An attacker who knows all of it still cannot make the two
paths take different amounts of time — the signal is gone, not hidden.

**The dummy comes from the verifier, so it matches the format you store.** This
is the part that is easy to get wrong. A bcrypt dummy equalises the two paths
only when the stored hashes are also bcrypt. Put one in front of an htpasswd
file holding `$apr1$` or `{SHA}` entries, or a column holding legacy `hash()`
digests, and it does not close the gap — it *inverts* it, and widens it. Those
formats verify in microseconds, so the unknown username becomes the slow answer
by a far larger margin than the original bug:

| stored format | wrong password | unknown username, bcrypt dummy |
|---|---|---|
| bcrypt, cost 12 | 259 ms | 265 ms |
| `hash('sha256', …)` | 0.001 ms | 261 ms |
| htpasswd `$apr1$` | 0.0002 ms | 261 ms |

Only the verifier knows which format it reads, so the verifier supplies the
dummy. Both stock verifiers implement `Verifier\DummyHashInterface`:
`PasswordVerifier` builds one from its configured algorithm and options, and
`HtpasswdVerifier` from the format named in its constructor. With the dummy
format-matched, every row above comes back to a ratio of about 1.

So the knob for pinning the cost is the **verifier**, not the adapter:

```php
<?php
// the dummy follows automatically -- cost 13 in, cost 13 dummy out
$verifier = new \Aura\Auth\Verifier\PasswordVerifier(
    PASSWORD_BCRYPT,
    array('cost' => 13)
);

// htpasswd -B writes bcrypt; the default here is apr1, what plain htpasswd writes
$verifier = new \Aura\Auth\Verifier\HtpasswdVerifier('bcrypt');

// a `$2y$` hash encodes its cost, and htpasswd -B writes cost 5 by default
// (`-C` sets it) where PHP writes 10, or 12 from PHP 8.4 on -- so a bcrypt
// htpasswd file needs the cost pinned too, or the dummy costs ~128x the
// wrong-password path and the signal is back, pointing the other way
$verifier = new \Aura\Auth\Verifier\HtpasswdVerifier('bcrypt', array('cost' => 5));
?>
```

`HtpasswdVerifier`'s format argument affects nothing but the dummy — `verify()`
still dispatches per entry, so a file mixing formats still authenticates
everyone. Set it to whatever the bulk of the file holds, since it is the cost of
the *typical* entry that the unknown-username path has to match.

`AbstractAdapter::getDummyHash()` remains as a fallback for verifiers that do
not implement `DummyHashInterface`, and still selects by PHP's default bcrypt
cost — 10 before PHP 8.4, 12 from 8.4 on. A custom verifier can either implement
the interface or leave the adapter to override:

```php
<?php
class MyPdoAdapter extends \Aura\Auth\Adapter\PdoAdapter
{
    protected function getDummyHash(): string
    {
        // generated once with password_hash() at the same cost as the hashes
        // in the accounts table; the plaintext was never recorded
        return '$2y$13$...';
    }
}
?>
```

**A dummy must be valid, and its plaintext unknown.** On the `password_verify()`
and `crypt()` paths a malformed hash is rejected without any hashing work, which
silently restores the timing difference; and a dummy whose plaintext someone
knows becomes a working password the moment the value is copied into a password
column. Generating it from `random_bytes()` and never recording the input
satisfies both, which is what the stock verifiers do. (The legacy `hash()` path
is the exception to the first half: it digests the submitted password before
comparing, so a malformed stored value still costs the same — the second half,
the unknown plaintext, applies there as much as anywhere.)

If the costs do not match exactly, a proportional difference remains. Login
throttling is what covers that residue: reading a small timing difference takes
many samples per username, and the backoff in
[Login Throttling](throttling.md) makes collecting them impractical.

### Response Time on LDAP and IMAP (not closed)

Everything above belongs to the two adapters that verify a hash themselves,
`PdoAdapter` and `HtpasswdAdapter`. `LdapAdapter` verifies nothing — the
directory does — and one of its two modes still answers an unknown username
faster than a wrong password.

| mode | unknown username | wrong password |
|---|---|---|
| direct bind (no `search`) | one bind | one bind |
| bind-search-rebind (`search` set) | search only | search, then a second bind |

**Direct bind is uniform.** The username goes into the distinguished name (DN)
template — an entry's full path in the directory tree, such as
`uid=%s,ou=people,dc=example,dc=com` — and the adapter binds once; an unknown
user and a wrong password both fail that same single bind, so the library does
identical work either way.

**Bind-search-rebind is not.** A search matching nothing throws
`UsernameNotFound` straight away, while a search that matches costs another
round trip — the rebind as the discovered user — before `BindFailed`. The gap is
roughly one LDAP round trip.

**It is deliberately not patched the way the hash adapters are,** because the
obvious cure is plausibly worse than the disease. A throwaway bind against a
deliberately nonexistent DN would send the submitted password to the directory
and land a failed-bind record in its audit log on every unknown username.
Directories commonly drive intruder detection and account lockout from exactly
that signal, so manufacturing failed binds at attacker-controlled volume is a
good way to cause an outage. It might not even work: a server that rejects an
unknown DN before comparing credentials short-circuits, leaving you with the
audit-log cost and the leak both.

What to do about it, in order of preference. Use direct bind where your
directory structure allows a DN template, since that mode has no gap. Otherwise
rely on [Login Throttling](throttling.md): one LAN round trip is a far smaller
and noisier signal than a bcrypt verification — milliseconds against hundreds of
milliseconds, read through network jitter — so it takes many samples per
username, and backoff is what makes collecting them impractical.

`ImapAdapter` draws no distinction of its own: it makes a single `imap_open()`
call and reports every failure as `ConnectionFailed`. Whether your IMAP server
rejects an unknown mailbox faster than a bad password is the server's behaviour,
and outside the library's reach.

### Error Messages (your responsibility)

None of the above matters if the response says which part failed.

Adapters throw distinct exceptions — `UsernameNotFound` when no account
matched, `PasswordIncorrect` when the password did not verify. That distinction
is useful for *your logs*. Shown to the user, it hands over exactly what the
timing fix was protecting, and far more cheaply: no statistics required, just
read the page.

Catch the base `Aura\Auth\Exception` and render one message for every failure:

```php
<?php
use Aura\Auth\Exception as AuthException;

try {
    $login_service->login($auth, array(
        'username' => $_POST['username'],
        'password' => $_POST['password'],
    ));
} catch (AuthException $e) {
    // log the specific exception; tell the user nothing specific
    $logger->info('login failed: ' . get_class($e));
    echo "Invalid username or password.";
}
?>
```

The same applies to anything else that varies with the outcome: HTTP status
codes, redirect targets, validation errors rendered next to one field rather
than the other, and response bodies whose length differs.

### Related Flows (your responsibility)

Login is rarely the easiest enumeration target. Password reset, registration,
and "resend confirmation" commonly answer "no account with that email" outright.
Those flows live in your application, not in Aura.Auth, and they deserve the
same generic response: *if that account exists, we have sent it an email.*

## Password Comparison

Verifiers compare hashes with `hash_equals()` rather than `===`, so comparison
time does not depend on how many leading characters matched. `PasswordVerifier`
delegates to `password_verify()` for bcrypt, which is constant-time already.
Custom verifiers implementing `VerifierInterface` should use `hash_equals()`
for the same reason.

## Password Storage

Constant-time comparison is only worth having if what is being compared is
worth protecting. `new PasswordVerifier('md5')` — or any other `hash()`
algorithm name — compares an unsalted digest with no work factor, which is
recoverable in bulk regardless of how carefully it is compared.

That configuration exists only to let a site with a legacy password column
authenticate users while migrating them. New applications should use
`new PasswordVerifier(PASSWORD_BCRYPT)`.

Migrating ones should rehash on each successful login — the only moment the
plaintext is available — until the old digests are gone. `needsRehash()` on
the adapter reports when the hash that just verified should be replaced, which
covers a raised bcrypt cost as well as legacy digests. See [Rehashing Stored
Passwords](adapters.md) for the recipe.

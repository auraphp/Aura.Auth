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

Two things are worth knowing about how it works.

**It does not rely on the dummy hash being secret.** `AbstractAdapter` exposes
the hashes as public constants and this documentation describes the mechanism
exactly. An attacker who knows all of it still cannot make the two paths take
different amounts of time — the signal is gone, not hidden.

**It matches PHP's default bcrypt cost**, which changed from 10 to 12 in PHP
8.4; `getDummyHash()` selects a hash accordingly. That default is only a proxy
for what actually matters, which is the cost of the hashes already in your
storage. Accounts keep the cost they were created with until rehashed, so a site
whose hashes predate a cost change — or one that sets cost explicitly — can
override `getDummyHash()` to return one generated at the cost it really uses:

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

A replacement must be a *valid* bcrypt hash — `password_verify()` rejects a
malformed one immediately without hashing, which silently restores the timing
difference — and its plaintext must be unknown, so that it can never work as a
password if the value is ever copied into a password column.

If the costs do not match exactly, a proportional difference remains. Login
throttling is what covers that residue: reading a small timing difference takes
many samples per username, and the backoff in
[Login Throttling](throttling.md) makes collecting them impractical.

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

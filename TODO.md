# TODO

## OAuth 2.0 — Follow-ups

- Ship provider-specific presets (field maps for common providers) so even the
  `username_field` line is unnecessary. Deferred: more to maintain.
- OIDC (`id_token`) handling and token refresh. Deferred: larger surface; leave
  to the client for now.

## Remember Me

Track `created_at`/`last_used_at` on remember-me tokens, for a device list and
revoking a cookie left on a lost phone? Deferred: nobody has asked, and
`RememberStorageInterface` is pluggable enough that a deployment wanting it can
subclass `PdoRememberStorage` without a core change. The absence is documented
in `docs/remember-me.md`, including why `expires` is not a stand-in.

## Verifiers

Build an HttpDigestVerifier based on <http://php.net/manual/en/features.http-auth.php> and/or <http://evertpot.com/223/>.

## Security

Track IP numbers through _ResumeService_? Deferred: breaks behind proxies, and
the trusted-proxy/`X-Forwarded-For` configuration it would need is the same
rabbit hole we avoided by keying login throttling on username alone.

## Throttling

Login-attempt throttling is done — see `src/Throttle/`, `Adapter/ThrottleAdapter`
and `docs/throttling.md`.

Throttling page loads by counting session "resumes" was considered and rejected:
it only sees requests that carry a session cookie, so an attacker bypasses it by
dropping the cookie, while legitimate logged-in users pay a storage read+write on
every request. Request rate limiting belongs upstream (nginx `limit_req`, a CDN,
or PSR-15 middleware), before the session is even started. If the underlying need
is spotting an account resuming abnormally often, that is anomaly detection for
the application to own, not throttling.

# TODO

## Return Types (repo-wide)

Add native return type declarations across the rest of `src/` for consistency.
The new `Remember/*` classes and the `Session/*` classes are already typed; the
remainder are not: `Auth`, `AuthFactory`, all `Service/*` (`LoginService`,
`LogoutService`, `ResumeService`), the `Adapter/*` hierarchy, `Verifier/*`,
`Timer`, and `Phpfunc`.

Notes for the pass:

- The `AdapterInterface` / `AbstractAdapter` pair and every adapter subclass must
  change together to stay signature-compatible.
- Some methods return unions (e.g. `LoginService::forceLogin()` returns
  `string|false`); type them accordingly.
- Run the full suite afterward.

Deferred: to be done as a separate change after the remember-me merge.

## OAuth 2.0 — Follow-ups

- Ship provider-specific presets (field maps for common providers) so even the
  `username_field` line is unnecessary. Deferred: more to maintain.
- OIDC (`id_token`) handling and token refresh. Deferred: larger surface; leave
  to the client for now.

## Remember Me — Follow-ups

- On resume, optionally reload user details from the DB in case of admin changes
  to the user. Cf.
  <https://github.com/craigrodway/LoginPersist/blob/master/LoginPersist.module>
  and other implementations for ideas.

## Verifiers

Build an HttpDigestVerifier based on <http://php.net/manual/en/features.http-auth.php> and/or <http://evertpot.com/223/>.

## Security

Track IP numbers through _ResumeService_? This may break with proxies.

## Throttling

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling brute-force of logins.


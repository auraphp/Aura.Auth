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

## Built-in OAuth 2.0 Adapter

Today, OAuth requires each user to hand-write a full `AdapterInterface`
implementation that wraps an OAuth2 client (see the "OAuth Adapters" section in
`docs/adapters.md`). The flow is almost identical across providers, so we should
ship a generic, configurable adapter and a factory method so people don't have
to write boilerplate for every provider.

Proposed design:

- Add `Aura\Auth\Adapter\OAuth2Adapter` implementing `AdapterInterface`. It runs
  the standard authorization-code exchange: take the `code` from `$input`,
  exchange it for an access token, fetch the resource owner, and return
  `[$username, $userdata]`.
- Do **not** hard-depend on any OAuth client. Define a tiny provider seam (e.g.
  `Aura\Auth\OAuth\ProviderInterface` with `getAuthorizationUrl()`,
  `getAccessToken($code)`, `getResourceOwner($token)`) and ship a thin wrapper
  for `league/oauth2-client`'s `AbstractProvider`. List `league/oauth2-client`
  under `suggest` (and `require-dev` for tests) so the core stays
  dependency-light.
- Make the resource-owner → auth mapping configurable so providers that return
  different field names all work without new code:
    - `username_field` (e.g. `email` or `login`), and/or
    - a `map` callback `fn($resourceOwner, $token): array [$username, $userdata]`
      for full control.
- Add `AuthFactory::newOAuth2Adapter(ProviderInterface $provider, array $options = [])`
  so wiring matches the other `new*Adapter()` methods.
- Provide a small helper/example for the redirect + callback halves of the flow
  (the `getAuthorizationUrl()` redirect, then `login($auth, $_GET)` on callback),
  and document per-provider option snippets (GitHub, Google, etc.).
- Update `docs/adapters.md`: keep the "write your own" section for advanced
  cases, but lead with the provided adapter + factory as the default path.

Open questions:

- Do we ship provider-specific presets (field maps for common providers), or
  only the generic mapping? Presets are the most "no code" but add maintenance.
- OIDC/`id_token` handling and token refresh — in scope, or leave to the client?

## Remember Me — DONE (6.0.0)

Implemented via `Aura\Auth\Remember\RememberService` using the split-token
(selector/validator) scheme with server-side storage (`RememberStorageInterface`
/ `PdoRememberStorage`), per-use token rotation, `Status::REMEMBERED` /
`Auth::isRemembered()`, and optional wiring into the login/logout/resume
services. See `docs/remember-me.md`.

Possible follow-ups not yet done:

- On resume, optionally reload user details from the DB in case of admin changes
  to the user.

Cf. <https://github.com/craigrodway/LoginPersist/blob/master/LoginPersist.module> and perhaps other implementations for ideas and insight.

## Verifiers

Build an HttpDigestVerifier based on <http://php.net/manual/en/features.http-auth.php> and/or <http://evertpot.com/223/>.

## Security

Track IP numbers through _ResumeService_? This may break with proxies.

## Throttling

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling brute-force of logins.


# TODO

## OAuth 2.0 — Follow-ups

- Ship provider-specific presets (field maps for common providers) so even the
  `username_field` line is unnecessary. Deferred: more to maintain.
- OIDC (`id_token`) handling and token refresh. Deferred: larger surface; leave
  to the client for now.

## Remember Me — Follow-ups

- Remember-Me stores a snapshot of user data in the token and replays it on
  resume. Add an optional user-loader seam so resume can re-fetch fresh user
  details from the source (picks up admin-side changes to roles/email/etc.). Cf.
  <https://github.com/craigrodway/LoginPersist/blob/master/LoginPersist.module>
  and other implementations for ideas.

## Verifiers

Build an HttpDigestVerifier based on <http://php.net/manual/en/features.http-auth.php> and/or <http://evertpot.com/223/>.

## Security

Track IP numbers through _ResumeService_? This may break with proxies.

## Throttling

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling brute-force of logins.


# OAuth 2.0

Aura.Auth ships an `OAuth2Adapter` and a secure authorization-code flow helper,
so authenticating against an OAuth 2.0 provider is **configuration, not code**
for any provider that already has a client library.

## Installation

The adapter talks to providers through a small seam, `Aura\Auth\OAuth\ProviderInterface`,
so the core has no hard OAuth dependency. A `LeagueProvider` wrapper for
[league/oauth2-client](https://github.com/thephpleague/oauth2-client) is
included. Install that client and a provider package for your service, for
example:

```
composer require league/oauth2-client league/oauth2-google
```

(Many providers exist — GitHub, Google, Facebook, and numerous community ones.
Only the rare service without an existing client requires you to implement
`ProviderInterface` yourself.)

## Instantiation

Wrap your configured League provider and create the adapter, mapping the
resource owner to a user name. Use `username_field` for the simple case, or a
`map` callback for full control:

```php
<?php
use Aura\Auth\OAuth\LeagueProvider;
use League\OAuth2\Client\Provider\Google;

$google = new Google([
    'clientId'     => 'xxxxxxxx',
    'clientSecret' => 'xxxxxxxx',
    'redirectUri'  => 'https://example.com/callback',
]);

$provider = new LeagueProvider($google);

// simplest: use a resource-owner field as the user name
$oauth_adapter = $auth_factory->newOAuth2Adapter($provider, [
    'username_field' => 'email',
]);

// or map fully:
$oauth_adapter = $auth_factory->newOAuth2Adapter($provider, [
    'map' => function (array $owner, $token) {
        return [$owner['email'], ['name' => $owner['name'], 'token' => $token]];
    },
]);
?>
```

### Mapping Options

`newOAuth2Adapter()` accepts exactly two options — `username_field` and `map` —
which are two alternative strategies for the same job: turning the provider's
**resource owner** into the `[$username, $userdata]` pair that Aura.Auth stores
(retrievable afterward as `$auth->getUserName()` and `$auth->getUserData()`).

On login the adapter exchanges the authorization code for a token, calls the
provider's `getResourceOwner()`, and receives the resource owner as an
**associative array** (`$owner`) whose keys are defined by the provider and the
scopes you requested — for Google, for example, `sub`, `email`, `name`,
`given_name`, `picture`, and so on.

- **`username_field`** — the name of a resource-owner field to use as the user
  name. The **entire** resource-owner array is retained as the user data, so
  other fields remain available:

  ```php
  $oauth_adapter = $auth_factory->newOAuth2Adapter($provider, [
      'username_field' => 'email',
  ]);

  // after login:
  $auth->getUserName();         // 'jane@example.com'  (the 'email' field)
  $auth->getUserData()['name']; // 'Jane Doe'          (also 'picture', etc.)
  ```

- **`map`** — a callable `fn(array $owner, $token): array` that returns your own
  `[$username, $userdata]` pair, for full control over the user name and exactly
  what is stored (rename keys, drop fields, keep the token, use a non-email user
  name, provide a fallback, and so on):

  ```php
  $oauth_adapter = $auth_factory->newOAuth2Adapter($provider, [
      'map' => function (array $owner, $token) {
          return [
              $owner['email'],                               // -> getUserName()
              ['name' => $owner['name'], 'token' => $token], // -> getUserData()
          ];
      },
  ]);

  // after login:
  $auth->getUserName();          // 'jane@example.com'
  $auth->getUserData()['name'];  // 'Jane Doe'
  $auth->getUserData()['token']; // the access token you chose to keep
  ```

Normally you set one or the other. If both are given, `map` takes precedence. If
**neither** is configured, the adapter throws
`Aura\Auth\Exception\OAuth2MappingNotConfigured` on login.

## The Secure Flow

Do **not** hand the raw `$_GET` to `login()`. Use the `AuthorizationCodeFlow`
helper, which generates and validates an anti-CSRF `state` (and a PKCE
`code_verifier` when the provider supports it), handles provider errors, and
returns only the validated parameters. It persists `state`/`code_verifier` in
the session segment, so it must run with a real session.

**Redirect half** — send the user to the provider. `newOAuth2Flow()` uses the
factory's own session segment to persist the `state`/`code_verifier`, so make
sure a session is running first:

```php
<?php
session_start(); // so the state persists in $_SESSION across the redirect

$flow = $auth_factory->newOAuth2Flow($provider);

header('Location: ' . $flow->getRedirectUrl(['scope' => ['email', 'profile']]));
exit;
?>
```

> **Using Aura.Session instead of `session_start()`.** If you hand the factory an
> [Aura.Session](https://github.com/auraphp/Aura.Session)-managed session and
> segment, the segment starts/resumes the session for you on first write, so you
> don't call `session_start()` yourself:
>
> ```php
> <?php
> use Aura\Auth\AuthFactory;
> use Aura\Session\SessionFactory;
>
> $session = (new SessionFactory)->newInstance($_COOKIE);
> $segment = $session->getSegment('Aura\Auth\Auth');
>
> // inject both; the OAuth flow, Auth, and the services all reuse this segment
> $auth_factory = new AuthFactory($_COOKIE, $session, $segment);
>
> // no session_start() needed — getRedirectUrl()/handleCallback() persist
> // state through the Aura.Session segment
> ?>
> ```
>
> Use the **same** `$auth_factory` (and thus the same `$segment`) for both the
> redirect and callback halves, so the `state`/`code_verifier` written in the
> redirect are the ones read back in the callback. See
> [Session Management](sessions.md) for details.

**Callback half** — validate, then log in with the validated input only:

```php
<?php
use Aura\Auth\Exception;

session_start(); // same session as the redirect, to read the stored state

$flow = $auth_factory->newOAuth2Flow($provider);
$login_service = $auth_factory->newLoginService($oauth_adapter);

try {
    // throws on a provider error, a bad/missing state (CSRF), or a missing code
    $input = $flow->handleCallback($_GET);

    // $input is only the validated ['code' => ..., 'code_verifier' => ...]
    $login_service->login($auth, $input);
    echo "You are now logged in as " . $auth->getUserName();

} catch (Exception\OAuth2CallbackError $e) {
    // the provider reported an error (e.g. the user denied access)
} catch (Exception\OAuth2StateMismatch $e) {
    // state missing or did not match — possible CSRF; do not log in
} catch (Exception\AuthorizationCodeMissing $e) {
    // no authorization code present
}
?>
```

> N.b.: The `AuthorizationCodeFlow` and `OAuth2Adapter` must share the same
> `$provider` instance within a request, and the segment must be session-backed
> so the `state` (and PKCE verifier) survive the redirect round-trip.

## Writing Your Own Provider

For a service with no existing client, implement `Aura\Auth\OAuth\ProviderInterface`
(`getAuthorizationRequest()`, `getAccessToken()`, `getResourceOwner()`) over
whatever client you use, and pass it to `newOAuth2Adapter()` / `newOAuth2Flow()`
exactly as above. You then get the same secure flow without re-implementing it.

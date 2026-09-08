# Session Management

The _Service_ objects use a _Session_ object to start sessions and regenerate session IDs. (Note that they **do not** destroy sessions.) The _Session_ object uses the native PHP `session_*()` functions to manage sessions.

## Using Aura.Session

You don't have to write a custom wrapper to use a full-featured session library.
Since 7.0.0, `AuthFactory` (and everything it builds — `Auth`, the
`LoginService` / `LogoutService` / `ResumeService`, and the OAuth
`AuthorizationCodeFlow`) type-hints the shared `Aura\Session_Interface`
contracts. [Aura.Session](https://github.com/auraphp/Aura.Session)'s own
`Session` and `Segment` implement those contracts, so they drop straight in:

```php
<?php
use Aura\Auth\AuthFactory;
use Aura\Session\SessionFactory;

$session = (new SessionFactory)->newInstance($_COOKIE);
$segment = $session->getSegment('Aura\Auth\Auth');

$auth_factory = new AuthFactory($_COOKIE, $session, $segment);
?>
```

Install it with `composer require aura/session`.

Because an Aura.Session segment starts (or resumes) the session for you the
first time a value is written to it, you no longer need to call `session_start()`
by hand — the _LoginService_, _ResumeService_, and the OAuth flow all persist
their data through the injected segment. Just be sure to build both halves of a
flow from the **same** `$auth_factory`, so they share one segment.

## Custom Sessions

If you wish to use an alternative means of managing sessions, implement the _SessionInterface_ on an object of your choice. One way to do this is by by wrapping a framework-specific session object and proxying the _SessionInterface_ methods to the wrapped object:

```php
<?php
use Aura\Auth\Session\SessionInterface;

class CustomSession implements SessionInterface
{
    protected $fwsession;

    public function __construct(FrameworkSession $fwsession)
    {
        $this->fwsession = $fwsession;
    }

    public function start()
    {
        return $this->fwsession->startSession();
    }

    public function resume()
    {
        if ($this->fwsession->isAlreadyStarted()) {
            return true;
        }

        if ($this->fwsession->canBeRestarted()) {
            return $this->fwsession->restartSession();
        }

        return false;
    }

    public function regenerateId()
    {
        return $this->fwsession->regenerateSessionId();
    }
}
?>
```

Then pass that custom session object to the _AuthFactory_ instantiation:

```php
<?php
use Aura\Auth\AuthFactory;

$custom_session = new CustomSession(new FrameworkSession);
$auth_factory = new AuthFactory($_COOKIE, $custom_session);
?>
```

The factory will pass your custom session object wherever it is needed.

## Working Without Sessions

In some situations, such as with APIs where credentials are provided with every request, it may be beneficial to avoid sessions altogether. In this case, pass a _NullSession_ and _NullSegment_ to the _AuthFactory_:

```php
<?php
use Aura\Auth\AuthFactory;
use Aura\Auth\Session\NullSession;
use Aura\Auth\Session\NullSegment;

$null_session = new NullSession;
$null_segment = new NullSegment;
$auth_factory = new AuthFactory($_COOKIE, $null_session, $null_segment);
?>
```

With the _NullSession_, no session will ever be started, and no session ID will be created or regenerated. Likewise, no session will ever be resumed, because it will never have been saved at the end of the previous request. Finally, PHP will never create a session cookie to send in the response.

Similarly, the _NullSegment_ retains authentication information in an object property instead of in a `$_SESSION` segment. Unlike the normal _Segment_, which only retains data when `$_SESSION` is present, the _NullSegment_ will always retain data that is set into it. When the request is over, all information retained in the _NullSegment_ will disappear.

When using the _NullSession_ and _NullSegment_, you will have to check  credentials via the _LoginService_ `login()` or `forceLogin()` method on each request, which in turn will retain the authentication information in the _Segment_. In an API situation this is often preferable to managing an ongoing session.

> N.b. In an API situation, the credentials may be an API token, or passed as HTTP basic or digest authentication headers.  Pass these to the adapter of your choice.

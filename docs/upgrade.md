# Upgrade Guide: 4.x → 6.0.0

This guide summarizes the changes in 6.0.0 that affect existing code. For the
full list, see the [CHANGELOG](https://github.com/auraphp/Aura.Auth/blob/6.x/CHANGELOG.md).

## PHP Version Requirement

PHP 8.1 or later is now required.

## New Dependency: `aura/session-interface`

Aura.Auth now depends on the [`aura/session-interface`](https://packagist.org/packages/aura/session-interface)
(`^6.0`) package, which provides the shared session and segment contracts. This
is installed automatically via Composer.

## Shared Session Contracts

`AuthFactory`, `Auth`, and the `LoginService` / `LogoutService` / `ResumeService`
now type-hint the shared `Aura\Session_Interface\SessionInterface` and
`Aura\Session_Interface\SegmentInterface`.

As a result, an `Aura\Session\Session` and its segments — or any other
implementation of the shared contracts — can now be passed to Aura.Auth
directly, while the built-in light `Session` / `Segment` continue to work
standalone.

The package's own `Aura\Auth\Session\SessionInterface` and
`Aura\Auth\Session\SegmentInterface` are **deprecated**. They now simply extend
the shared interfaces and are retained only for backward compatibility;
type-hint the `Aura\Session_Interface` versions instead.

## Nullable Type Declarations

Native type declarations were added to `Session`, `Segment`, `NullSession`, and
`NullSegment` to match the shared interfaces, and explicit nullable types
(`?Type`) are now used for parameters that default to `null` in `AuthFactory`
and `ImapAdapter`. If you extended any of these classes, align your method
signatures accordingly.

## New Feature: Remember Me

6.0.0 adds "remember me" support (issue #4):

- A new `Status::REMEMBERED` constant and `Auth::isRemembered()` method mark
  users re-authenticated from a cookie as **lower privilege** than credentialed
  (`VALID`) users.
- A new `Aura\Auth\Remember\RememberService` issues, resumes, and forgets
  long-lived login tokens using the split-token scheme with server-side storage
  and per-use rotation.
- Storage is pluggable via `Aura\Auth\Remember\RememberStorageInterface`, with a
  PDO-backed `PdoRememberStorage` provided.
- `LoginService`, `LogoutService`, and `ResumeService` accept an **optional**
  `RememberService` as a new, nullable, appended constructor parameter — so this
  is backward compatible and existing wiring is unaffected.

Build the pieces with `AuthFactory::newRememberService()` and
`AuthFactory::newPdoRememberStorage()`. See the [Remember Me](remember-me.md)
chapter for full setup.

## Tooling

The test suite now requires `phpunit/phpunit` `^11.0`, and the CI matrix targets
PHP 8.2 / 8.3 / 8.4. The `vimeo/psalm` dev dependency has been removed. These
changes affect contributors only, not consumers of the library.

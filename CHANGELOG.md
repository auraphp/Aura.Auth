# CHANGELOG

## 6.0.0

- PHP 8.2+ is now required.
- (ADD) "Remember me" support (issue #4). A new `Aura\Auth\Remember\RememberService` issues, resumes, and forgets long-lived login tokens using the split-token (selector/validator) scheme with server-side storage and per-use token rotation. Storage is pluggable via `RememberStorageInterface`, with a PDO-backed `PdoRememberStorage` provided. A new `Status::REMEMBERED` (and `Auth::isRemembered()`) marks users re-authenticated from a cookie as lower privilege than credentialed (`VALID`) users. The `LoginService`, `LogoutService`, and `ResumeService` accept an optional `RememberService` so the feature works through existing call sites; the added constructor parameters are nullable and backward compatible. Build the pieces with `AuthFactory::newRememberService()` and `AuthFactory::newPdoRememberStorage()`. See the README "Remember Me" section.
- (ADD) Depend on the new `aura/session-interface` (`^6.0`) package, which provides the shared session/segment contracts.
- (CHG) `AuthFactory`, `Auth`, and the `LoginService`/`LogoutService`/`ResumeService` now type-hint the shared `Aura\Session_Interface\SessionInterface` and `SegmentInterface`. As a result an `Aura\Session\Session` and its segments — or any other implementation of the shared contracts — can now be passed to Aura.Auth directly, while the built-in light `Session`/`Segment` continue to work standalone.
- (CHG) `Aura\Auth\Session\SessionInterface` and `Aura\Auth\Session\SegmentInterface` are deprecated. They now simply extend the shared interfaces and are retained only for backward compatibility; type-hint the `Aura\Session_Interface` versions instead.
- (CHG) Add native `string`/`mixed`/`bool`/`void` type declarations to `Session`, `Segment`, `NullSession`, and `NullSegment` to match the shared interfaces.
- (CHG) Use explicit nullable types (`?Type`) for parameters that default to `null` in `AuthFactory` and `ImapAdapter`, fixing the PHP 8.4 implicit-nullable deprecation.
- (TST) Update test doubles and test classes for PHP 8.2 (declared properties instead of dynamic ones).
- (TST) Require `phpunit/phpunit` `^11.0` and modernize the suite for it: mock proxied `Phpfunc` functions via real test doubles with `onlyMethods()` (replacing the removed `addMethods()`), use `willReturn()` instead of `will($this->returnValue())`, and migrate doc-comment metadata (`@runInSeparateProcess`, `@runTestsInSeparateProcesses`, `@preserveGlobalState`) to PHP attributes. The CI matrix now targets PHP 8.2/8.3/8.4.
- (CHG) Remove Psalm: drop the `vimeo/psalm` dev dependency, the `psalm.xml` config, and the Psalm CI job.

## 4.0.3

- (CHG) Make the library compatible with PHP 7.4 and 8.0.

## 4.0.2

- (FIX) Fix a typo/parameter type in a documentation block.

## 4.0.1

- (FIX) Work correctly with Aura.Di 4.x: `define()` and `modify()` now specify a `void` return type as required by strict type checking on PHP 7.2+.

## 4.0.0

New 4.x branch for modern PHP and PHPUnit usage.

- PHP 7.2+ is now required.
- (CHG) Upgrade to PHPUnit 7+ requirements and syntax.
- (CHG) Update dependency requirements to modern versions.
- (CHG) Replace Travis CI with GitHub Actions, and add Psalm (as a dev dependency).

## 2.0.1

- [Skip PDO tests if required pdo_sqlite extension not enabled](https://github.com/auraphp/Aura.Auth/pull/78)
- [Removed double closing happened in LDAP resources.](https://github.com/auraphp/Aura.Auth/pull/75)
- Update documentation fixing typos.
- phpunit added to require-dev of composer.
- Updated license year.
- Removed CHANGES.md file, added CHANGELOG.md instead.

## 2.0.0

First stable 2.0 release.

- (FIX) Correct AuthFactory namespace, and add test.

- (DOC) Additions and corrections in README.

- (FIX) Add missing Status in AdapterInterface.

## 2.0.0-beta2

- DOC: Updated README and docblocks.

- CHG: PdoAdapter::buildSelectWhere() now honors the custom column name provided by the user.

- CHG: Turn off auto-resolution in Container tests

## 2.0.0-beta1

Initial 2.0.0 beta release.

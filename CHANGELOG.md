# CHANGELOG

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

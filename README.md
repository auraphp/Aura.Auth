# Aura.Auth

Provides authentication functionality and session tracking using various adapters; currently supported adapters are:

- Apache htpasswd files
- SQL tables via the [PDO](http://php.net/pdo) extension
- IMAP/POP/NNTP via the [imap](http://php.net/imap) extension
- LDAP and Active Directory via the [ldap](http://php.net/ldap) extension
- OAuth via customized adapters

It also provides a secure, pluggable "remember me" feature for long-term authentication.

Note that the purpose of this package is only to authenticate user credentials. It does not currently, and probably will not in the future, handle user account creation and management. That is more properly the domain of application-level functionality, or at least a separate Aura bundle.

## Foreword

### Installation

This library requires PHP 8.1 or later, and depends only on [aura/session-interface](https://packagist.org/packages/aura/session-interface) for its shared session contracts.

It is installable and autoloadable via Composer as [aura/auth](https://packagist.org/packages/aura/auth).

Alternatively, [download a release](https://github.com/auraphp/Aura.Auth/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/auraphp/Aura.Auth/badges/quality-score.png?b=6.x)](https://scrutinizer-ci.com/g/auraphp/Aura.Auth/)
[![Continuous Integration](https://github.com/auraphp/Aura.Auth/actions/workflows/continuous-integration.yml/badge.svg?branch=6.x)](https://github.com/auraphp/Aura.Auth/actions/workflows/continuous-integration.yml)

To run the unit tests at the command line, issue `composer install` and then `vendor/bin/phpunit` at the package root. This requires [Composer](http://getcomposer.org/) to be available as `composer`.

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.

## Documentation

This package is fully documented [here](./docs/index.md).

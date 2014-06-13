# Aura.Auth

Provides authentication functionality and session tracking using various adapters; currently supported adapters are:

- Apache htpasswd files
- SQL tables via [PDO](http://php.net/pdo)

Note that the purpose of this package is only to authenticate user credentials; it does not currently, and probably will not in the future, handle user account creation and management. That is more properly the domain of application-level functionality, or at least a separate Aura bundle.

## Foreword

### Installation

This library requires PHP 5.4 or later, and has no userland dependencies.

> NOT YET: It is installable and autoloadable via Composer as [aura/auth](https://packagist.org/packages/aura/auth).

> NOT YET: Alternatively, [download a release](https://github.com/auraphp/Aura.Auth/releases) or clone this repository, then require or include its _autoload.php_ file.

### Quality

> NOT YET: Quality Badges

To run the [PHPUnit][] tests at the command line, go to the _tests_ directory and issue `phpunit`.

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PHPUnit]: http://phpunit.de/manual/
[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md


### Community

To ask questions, provide feedback, or otherwise communicate with the Aura community, please join our [Google Group](http://groups.google.com/group/auraphp), follow [@auraphp on Twitter](http://twitter.com/auraphp), or chat with us on #auraphp on Freenode.

### TODO

This package is in a very early development stage and as such is still in flux. Some things remain to be decided:

- The _Auth_ object embeds a _Session_ manager for starting/destoying sessions, regenerating IDs, and tracking session data specific to the Aura.Auth package. This behavior may be removed, modified, or otherwise broken in future commits; for example, we may decide it is best for the developer to manage the session lifecycle manually.

- Automatic resumption of previous sessions is currently not incorporated. Depending on how we decide to deal with session management, this may or may not be added.

- We still need to add IMAP and LDAP adapters. The ports of these from [Solar](http://solarphp.com) are still unfinished and are in the `hold/` directory.


## Getting Started

THIS DOCUMENTATION IS INCOMPLETE AND WRONG.

### Instantiation

TBD

### Login and Logout Functionality

#### Via Htpasswd

To create an _Auth_ object that uses the _HtpasswdAdapter_, call the `newHtpasswdInstace()` method and pass the file path of the Apache htpasswd file.

```
<?php
$auth = $auth_factory->newHtpasswdInstance('/path/to/accounts.htpasswd');
?>
```

This will automatically use the _HtpasswdVerifier_ to check DES, MD5, and SHA passwords from the htpasswd file on a per-user basis.


#### Via PDO Connection

To create an _Auth_ object that uses the _PdoAdapter_, call the `newPdoInstace()` method and pass these parameters in order:

- a _PDO_ connection instance

- a specification to indicate how passwords are hashed in the database:

    - if a string, it is treated as a `hash()` algorithm for a _HashVerifier_ instance

    - if a `PASSWORD_*` constant from PHP 5.5 and up, it is treated as `password_hash()` algorithm for a _PasswordVerifier_ instance

    - otherwise, it is expected to be an implementation of _VerifierInterface_

- an array of column names: the first element is the username column, the second element is the hashed-password column, and additional columns are used as extra user information to be selected and returned from the database

- a `FROM` specification string to indicate one or more table names, with any other `JOIN` clauses you wish to add

- an optional `WHERE` condition string; use this to add extra conditions to the `SELECT` statement built by the adapter

Here is a straightforward example where passwords are MD5 hashed in an accounts table:

```
<?php
$pdo = new \PDO(...);
$hash = 'md5';
$cols = ('username', 'md5password');
$from = 'accounts';
$auth = $auth_factory->newPdoInstance($pdo, $hash, $cols, $from);
?>
```

Here is a more complex example that uses bcrypt instead of md5, retrieves extra user information columns from joined tables, and filters for active accounts:

```
<?php
$pdo = new \PDO(...);
$hash = PASSWORD_BCRYPT;
$cols = array(
    'accounts.username', // "AS username" is added by the adapter
    'accounts.bcryptpass', // "AS password" is added by the adapter
    'accounts.uid AS uid',
    'userinfo.email AS email',
    'userinfo.uri AS website',
    'userinfo.fullname AS display_name',
);
$from = 'accounts JOIN userinfo ON accounts.uid = userinfo.uid';
$where = 'accounts.active = 1';
$auth = $auth_factory->newPdoInstance($pdo, $hash, $cols, $from);
?>
```

(The additional information columns will be retained in the session data after successful authentication.)

### Custom Session Integration

TBD


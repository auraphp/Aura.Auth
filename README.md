Aura.Auth
=========


AdapterFactory
---------------

```php
$user     = new User;
$adapters = [
    'ini' => new Aura\Auth\Adapter\Ini(
                $user,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'auth.ini'
            ),
    'htpasswd' =>   function () user ($user) { // closures are also supported
                        return new Aura\Auth\Adapter\Htpasswd($user, '/path/to/htpasswd');
                    }
];
$factory  = new AdapterFactory($adapters);

$is_valid = $factory->newInstance('ini')->authenticate(['username' => 'jane', 'password' => '12345']);
```

Auth
----

```php
$auth = new Auth($factory, new SessionManager(...));

// Validating a user.

$is_valid = $auth->validate('ini', ['username' => 'jane', 'password' => '12345']);

// If the user has validated and the session has not expired or idled `isVaild()` will return true on subsequent requests.
if ($auth->isValid()) {
    // user is logged in
}

```

Adapters
--------

### Ini

Each group is a user handle, with keys for `password` and the optional keys: `hash_algo`, `hash_salt`, `email`, `uri`, `avatar` and `full_name`.

**Example.ini:**

```ini 
 [johndoe]
 password = plaintextpass_or_hashedpass
 
 # Optional values:
  
 hash_algo = sha512          # hashing algorithm to use on the password
 hash_salt = a_random_string # hash this users password with this salt.
 
 email     = johndoe@example.com
 uri       = http://johndoe.example.com/
 avatar    = http://johndoe.example.com/avator.jpg
 full_name = John Doe
 ```

**Example Aura.Di config:**

```php
$di->params['Aura\Auth\AdapterFactory'] = [
    'adapters' => [
        'ini' => function() use ($di) {
            return $di->newInstance('Aura\Auth\Adapter\Ini', [ 
                'user' => $di->newInstance('Aura\Auth\User'),
                'file' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'auth.ini'
            ]);
        },
    ]
];
```

 ### Htpasswd

 **Supported encryption types:**

   * Apr1
   * SHA
   * DES

**Important Note on DES**

Note that `crypt()` will only check up to the first 8 characters of a password; chars after 8 are ignored. This means that if the real password is "atecharsnine", the word "atechars" would be valid. As a workaround, if the password provided by the user is longer than 8 characters, this adapter will *not* validate it.

**Example Aura.Di config:**

```php
$di->params['Aura\Auth\AdapterFactory'] = [
    'adapters' => [
        
    ]
];
```

### Mail

Validate a user name and password against a mail server.


**Example Aura.Di config:**

```php
$di->params['Aura\Auth\AdapterFactory'] = [
    'adapters' => [
        'acme_mail' => function() use ($di) {
            return $di->newInstance('Aura\Auth\Adapter\Mail', [ 
                'user' => $di->newInstance('Aura\Auth\User'),
                'mailbox' => 'imap.gmail.com:993/imap/ssl/novalidate-cert'
            ]);
        },
    ]
];
```
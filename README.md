# Aura.Auth

WIP and learnings

## Getting Started

```php
$auth = new Aura\Auth\Adapter\Ini(
    dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'auth.ini',
    'username',
    'password'
);
            
$auth->authenticate();

// alternatively you can set the username and password seprately
// $auth = new Aura\Auth\Adapter\Htpasswd('/path/to/htpasswd');
// $auth->setUsername('username');
// $auth->setUsername('password');
// $auth->authenticate();
```

## Adapters

### Ini

Each group is a user handle, with keys for `password` and the optional keys: `hash_algo`, `hash_salt`.

**Example.ini:**

```ini 
 [johndoe]
 password = plaintextpass_or_hashedpass
 
 # Optional values:
  
 hash_algo = sha512          # hashing algorithm to use on the password
 hash_salt = a_random_string # hash this users password with this salt.
 email     = johndoe@example.com # needs implementation
 uri       = http://johndoe.example.com/ # needs implementation
```
 
### Htpasswd

**Supported encryption types:**

   * Apr1
   * SHA
   * DES

**Important Note on DES**

Note that `crypt()` will only check up to the first 8 characters of a password; chars after 8 are ignored. 
This means that if the real password is "atecharsnine", the word "atechars" would be valid. 
As a workaround, if the password provided by the user is longer than 8 characters, this adapter will *not* validate it.

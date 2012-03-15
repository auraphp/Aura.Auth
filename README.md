

Adapters
--------

### Ini

Each group is a user handle, with keys for `password` and the optional keys: `hash_algo`, `hash_salt`, `email`, `uri`, `avatar` and `full_name`.

**Example.ini:**

```ini 
 [pmjones]
 password = plaintextpass_or_hashedpass
 
 # Optional values:
  
 hash_algo = sha512          # hashing algorithm to use on the password
 hash_salt = a_random_string # hash this users password with this salt. Format: hash_algo("{$password}{$hash_salt}")
 
 email     = pmjones@solarphp.com
 uri       = http://paul-m-jones.com/
 avatar    = http://paul-m-jones.com/avator.jpg
 full_name = Paul M. Jones
 ```

 ### Htpasswd

 **Supported encryption types:**

   * Apr1
   * SHA
   * DES

**Important Note on DES**

    Note that `crypt()` will only check up to the first 8 characters of a password; chars after 8 are ignored. This means that if the real password is "atecharsnine", the word "atechars" would be valid. As a workaround, if the password provided by the user is longer than 8 characters, this adapter will *not* validate it.
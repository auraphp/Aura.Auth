

Adapters
--------

### Ini

Each group is a user handle, with keys for `password` and the optional keys: `hash_algo`, `hash_salt`, `email`, `uri`, `avatar` and `full_name`.  For example:

```ini 
 [pmjones]
 password = plaintextpass
 
 # Optional values:
  
 hash_algo = sha512          # hashing algorithm to use on the password
 hash_salt = a_random_string # hash this users password with this salt. Format: hash_algo("{$password}{$hash_salt}")
 
 email     = pmjones@solarphp.com
 uri       = http://paul-m-jones.com/
 avatar    = http://paul-m-jones.com/avator.jpg
 full_name = Paul M. Jones
 ```
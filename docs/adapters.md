# Adapters

Forcing the _Auth_ object to a particular state is fine for when you want to exercise manual control over the authentication status, user name, user data, and other information. However, it is more often the case that you will want to check user credential input (username and password) against a credential store.  This is where the _Adapter_ classes come in.

To use an _Adapter_ with a _Service_, you first need to create the _Adapter_, then pass it to the _AuthFactory_ `new*Service()` method.

## Htpasswd Adapter

### Instantiation

To create an adapter for Apache htpasswd files, call the _AuthFactory_ `newHtpasswdAdapter()` method and pass the file path of the Apache htpasswd file.

```php
<?php
$htpasswd_adapter = $auth_factory->newHtpasswdAdapter(
    '/path/to/accounts.htpasswd'
);
?>
```

This will automatically use the _HtpasswdVerifier_ to check DES, MD5, and SHA passwords from the htpasswd file on a per-user basis.

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($htpasswd_adapter);
$logout_service = $auth_factory->newLogoutService($htpasswd_adapter);
$resume_service = $auth_factory->newResumeService($htpasswd_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

## IMAP/POP/NNTP Adapter

### Instantiation

To create an adapter for IMAP/POP/NNTP servers, call the _AuthFactory_ `newImapAdapter()` method and pass the mailbox specification string, along with any appropriate option constants:

```php
<?php
$imap_adapter = $auth_factory->newImapAdapter(
    '{mail.example.com:143/imap/secure}',
    OP_HALFOPEN
);
?>
```

> N.b.: See the [imap_open()](http://php.net/imap_open) documentation for more variations on mailbox specification strings.

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($imap_adapter);
$logout_service = $auth_factory->newLogoutService($imap_adapter);
$resume_service = $auth_factory->newResumeService($imap_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

## LDAP Adapter

### Instantiation

To create an adapter for LDAP and Active Directory servers, call the _AuthFactory_ `newLdapAdapter()` method and pass the server name with a distinguished name (DN) format string:

```php
<?php
$ldap_adapter = $auth_factory->newLdapAdapter(
    'ldaps://ldap.example.com:636',
    'ou=Company Name,dc=Department Name,cn=users,uid=%s'
);
?>
```

> N.b.: The username will be escaped and then passed to the DN format string via [sprintf()](http://php.net/sprintf). The completed DN will be used for binding to the server after connection.

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($ldap_adapter);
$logout_service = $auth_factory->newLogoutService($ldap_adapter);
$resume_service = $auth_factory->newResumeService($ldap_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)

## PDO Adapter

### Instantiation

To create an adapter for PDO connections to SQL tables, call the _AuthFactory_ `newPdoAdapter()` method and pass these parameters in order:

- a _PDO_ connection instance

- a indication of how passwords are hashed in the database:

    - if a `PASSWORD_*` constant from PHP 5.5 and up, it is treated as `password_hash()` algorithm for a _PasswordVerifier_ instance (this is the preferred method)

    - if a string, it is treated as a `hash()` algorithm for a _HashVerifier_ instance

    - otherwise, it is expected to be an implementation of _VerifierInterface_

- an array of column names: the first element is the username column, the second element is the hashed-password column, and additional columns are used as extra user information to be selected and returned from the database

- a `FROM` specification string to indicate one or more table names, with any other `JOIN` clauses you wish to add

- an optional `WHERE` condition string; use this to add extra conditions to the `SELECT` statement built by the adapter

Here is a legacy example where passwords are MD5 hashed in an accounts table:

```php
<?php
$pdo = new \PDO(...);
$hash = new PasswordVerifier('md5');
$cols = array('username', 'md5password');
$from = 'accounts';
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from);
?>
```

Here is a modern, more complex example that uses bcrypt instead of md5, retrieves extra user information columns from joined tables, and filters for active accounts:

```php
<?php
$pdo = new \PDO(...);
$hash = new PasswordVerifier(PASSWORD_BCRYPT);
$cols = array(
    'accounts.username', // "AS username" is added by the adapter
    'accounts.bcryptpass', // "AS password" is added by the adapter
    'accounts.uid AS uid',
    'userinfo.email AS email',
    'userinfo.uri AS website',
    'userinfo.fullname AS display_name',
);
$from = 'accounts JOIN profiles ON accounts.uid = profiles.uid';
$where = 'accounts.active = 1';
$pdo_adapter = $auth_factory->newPdoAdapter($pdo, $hash, $cols, $from, $where);
?>
```

(The additional information columns will be retained in the session data after successful authentication.)

### Service Integration

You can then pass the _Adapter_ to each _Service_ factory method like so:

```php
<?php
$login_service = $auth_factory->newLoginService($pdo_adapter);
$logout_service = $auth_factory->newLogoutService($pdo_adapter);
$resume_service = $auth_factory->newResumeService($pdo_adapter);
?>
```

To attempt a user login, pass an array with `username` and `password` elements to the _LoginService_ `login()` method along with the _Auth_ object:

```php
<?php
$login_service->login($auth, array(
    'username' => 'boshag',
    'password' => '12345'
));
?>
```

For more on _LoginService_ idioms, please see the [Service Idioms](service-idioms.md) section. (The _LogoutService_ and _ResumeService_ do not need credential information.)


## Custom Adapters

Although this package comes with multiple _Adapter_ classes, it may be that none of them fit your needs.

You may wish to extend one of the existing adapters to add login/logout/resume behaviors. Alternatively, you can create an _Adapter_ of your own by implementing the _AdapterInterface_ on a class of your choosing:

```php
<?php
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Auth;
use Aura\Auth\Status;

class CustomAdapter implements AdapterInterface
{
    // AdapterInterface::login()
    public function login(array $input)
    {
        if ($this->isLegit($input)) {
            $username = ...;
            $userdata = array(...);
            $this->updateLoginTime(time());
            return array($username, $userdata);
        } else {
            throw CustomException('Something went wrong.');
        }
    }

    // AdapterInterface::logout()
    public function logout(Auth $auth, $status = Status::ANON)
    {
        $this->updateLogoutTime($auth->getUsername(), time());
    }

    // AdapterInterface::resume()
    public function resume(Auth $auth)
    {
        $this->updateActiveTime($auth->getUsername(), time());
    }

    // custom support methods not in the interface
    protected function isLegit($input) { ... }

    protected function updateLoginTime($time) { ... }

    protected function updateActiveTime($time) { ... }

    protected function updateLogoutTime($time) { ... }
}
?>
```

You can then pass an instance of the custom adapter when creating services through the _AuthFactory_ methods:

```php
<?php
$custom_adapter = new CustomAdapter;
$login_service = $auth_factory->newLoginService($custom_adapter);
$logout_service = $auth_factory->newLogoutService($custom_adapter);
$resume_service = $auth_factory->newResumeService($custom_adapter);
?>
```

### OAuth Adapters

Should you desire to handle your authentication through a 3rd party service that uses OAuth 2.0, you'll need to write an adapter that implements ```Aura\Auth\Adapter\AdapterInterface``` and provide your own implementation for
fetching the access token and user information. Your implementation can be something you've written yourself or it can be an existing OAuth2 client.

The following example will demonstrate how you'd go about creating this adapter using the [PHP League's OAuth2 Client](https://github.com/thephpleague/oauth2-client). We'll also be using Github as the service provider for this example.

```php
<?php
namespace OAuth2\Adapter;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Exception;
use Aura\Auth\Auth;
use Aura\Auth\Status;
use League\OAuth2\Client\Provider\AbstractProvider;

class LeagueOAuth2Adapter implements AdapterInterface
{

    /**
     * @var \League\OAuth2\Client\Provider\IdentityProvider
     * The identity provider that the adapter will use
     */
    protected $provider;

    public function __construct(AbstractProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param $input an input containing the OAuth 2 code
     * @return array the username and details for the user
     * @throws \Aura\Auth\Exception
     * This method must be implemented to fulfill the contract
     * with AdapterInterface
     */
    public function login(array $input)
    {
        if (!isset($input['code'])) {
            throw new Exception('Authorization code missing.');
        }

        $token = $this->provider->getAccessToken(
            'authorization_code',
            array('code' => $input['code'])
        );

        $details = $this->provider->getResourceOwner($token);
        $data = [
            'name' => $details->getName(),
            'email' => $details->getEmail(),
        ];
        $data['token'] = $token;
        $username = $data['email'];
        return [$username, $data];
    }

    /**
     * @param Auth $auth
     * Logout method is required to fulfill the contract with AdapterInterface
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        //nothing to do here
    }

    /**
     * @param Auth $auth
     * Resume method required to fulfill the contract with AdapterInterface
     */
    public function resume(Auth $auth)
    {
        // nothing to do here
    }
}
?>
```

As you can see in the code, your adapter will be accepting a client as a
parameter and using that client to fulfill the
\Aura\Auth\Adapter\AdapterInterface contract. This adapter would be commonly
used in an OAuth2 Callback process. Essentially, once you provide your
credentials and authenticate with the 3rd Party service (in this case Github),
you will be redirected back to a script on your server where you'll have to
verify that you sent the request by sending an verification code back to the
service. This is why it's a good idea to use a good OAuth2 client in lieu of
writing your own. Below is an example of what this OAuth2 callback code might
look like.

```php
<?php
namespace OAuth2;

use Aura\Auth\AuthFactory;
use League\OAuth2\Client\Provider\Github;
use OAuth2\Adapter\LeagueOAuth2Adapter;
use Aura\Auth\Exception;

require_once 'vendor/autoload.php';

$auth_factory = new AuthFactory($_COOKIE);
$auth = $auth_factory->newInstance();

$github_provider = new Github(array(
    'clientId' => 'xxxxxxxxxxxxxxxx',
    'clientSecret' => 'xxxxxxxxxxxxxxxxxxxx',
    'redirectUri' => 'http://aura.auth.dev/'
));

if (!isset($_GET['code'])) {
    header('Location: ' . $github_provider->getAuthorizationUrl());
    exit;
} else {
    $oauth_adapter = new LeagueOAuth2Adapter($github_provider);
    $login_service = $auth_factory->newLoginService($oauth_adapter);
    try {
        // array is the username and an array of info and indicates successful
        // login
        $data = $login_service->login($auth, $_GET);
    } catch (Exception $e) {
        // handle the exception
    }
}
?>
```

The fact that not every 3rd Party Service returns data the same way means it's
not reasonable for Aura to try to handle every different data set out of the
box. By writing this little bit of code, you can easily implement Aura Auth for
your 3rd Party OAuth2 services.

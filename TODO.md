# TODO

## Security

Track IP numbers through _ResumeService_? This may break with proxies.

## Add OAuth Support

It might be that all one has to do is build a custom adapter and inject the provider of one's choice. The redirect URL given to the provider needs to be an action that invokes the LoginService. The adapter for that service can be based on, for example, the League client:

```php
<?php
namespace Custom\Auth\Adapter;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Exception;
use League\OAuth2\Client\Provider\AbstractProvider;

class LeagueOAuth2Adapter implements AdapterInterface
{
   public function __construct(AbstractProvider $provider)
   {
       $this->provider = $provider;
   }

   public function login($input)
   {
       if (! isset($input['code'])) {
           throw new Exception('Authorization code missing.')
       }

       $token = $this->provider->getAccessToken(
           'authorization_code',
           array('code' => $input['code'])
       );

       $details = $this->provider->getUserDetails($token);
       $data = $details->getArrayCopy();
       $data['token'] = $token;

       $username = $data['nickname'];
       unset($data['nickname']);

       return array($username, $data);
   }

   public function logout()
   {
       // do nothing
   }

   public function resume()
   {
       // do nothing
   }
}
?>
```

This could perhaps be provided as a bundle.

## Verifiers

Build an HttpDigestVerifier based on <http://php.net/manual/en/features.http-auth.php> and/or <http://evertpot.com/223/>.

## Remember Me

Add "remember me" functionality.

On "remember me" during login, store the a cryptographically secure token as a cookie. (Store username too?) Also keep in database.

On resume, if resume session fails, look for that cookie.

    If no cookie, no remember.

    If cookie, check against database.

        If cookie matches, remember that user into the session (Status::REMEMBERED). Update the token and cookie.

        If cookie no match, treat user as anon.

Also on resume, we may wish to add a DB check to reload session details; this is in case there have been admin changes to the user.

Cf. <https://github.com/craigrodway/LoginPersist/blob/master/LoginPersist.module> and perhaps other implementations for ideas and insight.

## Throttling

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling brute-force of logins.


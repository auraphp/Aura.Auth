# Security

Add bcrypt to _HtpasswdVerifier_ per <http://httpd.apache.org/docs/current/programs/htpasswd.html>

Add more-thorough session destruction to _Session_ per <http://php.net/session_destroy>.

# Adapters

Add hashing to the IniAdapter.

Import the LdapAdapter from hold/.

Import the MailAdapter from hold/.


# Remember Me

On "remember me" during login, store the a cryptographically secure token as a cookie. (Store username too?) Also keep in database.

On resume, if resume session fails, look for that cookie.

    If no cookie, no remember.

    If cookie, check against database.

        If cookie matches, remember that user into the session (Status::REMEMBERED). Update the token and cookie.

        If cookie no match, treat user as anon.

Also on resume, we may wish to add a DB check to reload session details; this is in case there have been admin changes to the user.

Cf. <https://github.com/craigrodway/LoginPersist/blob/master/LoginPersist.module> and perhaps other implementations for ideas and insight.

# Security/Throttling

Track IP numbers?

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling DOS attacks.

# Formless Authentication

HTTP basic is easy. Pass $cred = array('username' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']) to the handler. Or something like <http://evertpot.com/223/>.

HTTP digest is a little more tricky. Build a Verifier based on <http://php.net/manual/en/features.http-auth.php>, or on <http://evertpot.com/223/>.

OAuth is a different thing entirely.

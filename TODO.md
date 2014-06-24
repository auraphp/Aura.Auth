# General

Add htpaswd bcrypt: http://httpd.apache.org/docs/current/programs/htpasswd.html


# (?) Remember Me

On "remember me" during login, store the a cryptographically secure token as a cookie. (Store username too?) Also keep in database.

On resume, if resume session fails, look for that cookie.

    If no cookie, no remember.

    If cookie, check against database.

        If cookie matches, remember that user into the session (Status::REMEMBERED). Update the token and cookie.

        If cookie no match, treat user as anon.

Also on resume, we may wish to add a DB check to reload session details; this is in case there have been admin changes to the user.

# (?) Security/Throttling

Track IP numbers?

Track activity/page loads?  I.e., number of times we had to "resume" the session. This would be for throttling the page loads.

Track number of login attempts? This would be for throttling DOS attacks.

# (?) Non-Session Authentication

Use a custom session save handler that does nothing.

# (?) Formless Authentication

HTTP basic is easy. Pass $cred = array('username' => $_SERVER['PHP_AUTH_USER'], 'password' => $_SERVER['PHP_AUTH_PW']) to the handler. Or something like <http://evertpot.com/223/>.

HTTP digest is a little more tricky. Build a Verifier based on <http://php.net/manual/en/features.http-auth.php>, or on <http://evertpot.com/223/>.

OAuth is a different thing entirely.

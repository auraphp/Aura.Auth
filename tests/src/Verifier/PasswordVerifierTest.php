<?php
namespace Aura\Auth\Verifier;

/**
 * For PHP < 5.5 without ircmaxell/password_compat, fake the password_hash()
 * and password_verify() functions.
 */
if (! defined('PASSWORD_BCRYPT')) {
    function password_hash($plaintext, $algo, array $opts = array())
    {
        return hash($algo, $plaintext);
    }
    function password_verify($password, $hash)
    {
        return md5($password) === $hash;
    }
}

class PasswordVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        if (defined('PASSWORD_BCRYPT')) {
            // use the real password_hash()
            $algo = PASSWORD_BCRYPT;
        } else {
            // use the fake password_hash()
            $algo = 'md5';
        }

        $verifier = new PasswordVerifier($algo);
        $plaintext = 'password';
        $encrypted = password_hash($plaintext, $algo);
        $this->assertTrue($verifier($plaintext, $encrypted));
        $this->assertFalse($verifier('wrong', $encrypted));
    }
}

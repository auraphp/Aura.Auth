<?php
namespace Aura\Auth;

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
        return $password === $hash;
    }
}

class PasswordVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function testHash()
    {
        $verifier = new PasswordVerifier('hash', 'md5');
        $plaintext = 'password';
        $encrypted = hash('md5', $plaintext);
        $this->assertTrue($verifier($plaintext, $encrypted));
        $this->assertFalse($verifier('wrong', $encrypted));
    }

    public function testPasswordHash()
    {
        if (defined('PASSWORD_BCRYPT')) {
            // use the real password_hash()
            $algo = PASSWORD_BCRYPT;
        } else {
            // use the fake password_hash()
            $algo = 'md5';
        }

        $verifier = new PasswordVerifier('password_hash', $algo);
        $plaintext = 'password';
        $encrypted = password_hash($plaintext, $algo);
        $this->assertTrue($verifier($plaintext, $encrypted));
        $this->assertFalse($verifier('wrong', $encrypted));
    }

    public function testUnrecognized()
    {
        $this->setExpectedException('Aura\Auth\Exception');
        $verifier = new PasswordVerifier('no-such-function', 'no-such-algo');
    }
}

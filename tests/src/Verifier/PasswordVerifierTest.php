<?php
namespace Aura\Auth\Verifier;

class PasswordVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        if (defined('PASSWORD_BCRYPT')) {
            // use the real password_hash()
            $algo = PASSWORD_BCRYPT;
        } else {
            // use the fake password_hash()
            $this->markTestIncomplete("password_hash functionality not available. Install ircmaxell/password-compat for 5.3+");
        }

        $verifier = new PasswordVerifier($algo);
        $plaintext = 'password';
        $encrypted = password_hash($plaintext, $algo);
        $this->assertTrue($verifier->verify($plaintext, $encrypted));
        $this->assertFalse($verifier->verify('wrong', $encrypted));
    }
}

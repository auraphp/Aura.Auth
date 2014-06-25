<?php
namespace Aura\Auth\Verifier;

class PasswordVerifierTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (! defined('PASSWORD_BCRYPT')) {
            $this->markTestSkipped("password_hash functionality not available. Install ircmaxell/password-compat for 5.3+");
        }
    }

    public function test()
    {
        $algo = PASSWORD_BCRYPT;
        $verifier = new PasswordVerifier($algo);
        $plaintext = 'password';
        $encrypted = password_hash($plaintext, $algo);
        $this->assertTrue($verifier->verify($plaintext, $encrypted));
        $this->assertFalse($verifier->verify('wrong', $encrypted));
    }
}

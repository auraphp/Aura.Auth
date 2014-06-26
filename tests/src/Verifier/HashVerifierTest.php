<?php
namespace Aura\Auth\Verifier;

class HashVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $verifier = new HashVerifier('md5');
        $plaintext = 'password';
        $encrypted = hash('md5', $plaintext);
        $this->assertTrue($verifier->verify($plaintext, $encrypted));
        $this->assertFalse($verifier->verify('wrong', $encrypted));
    }

    public function testVerifyWhenSaltIsDifferent()
    {
        $salt = 'aura';
        $verifier = new HashVerifier('md5', $salt);
        $plaintext = 'password';
        $encrypted = hash('md5', $salt . $plaintext);
        $this->assertTrue($verifier->verify($plaintext, $encrypted));
        $salt_diff = 'hello';
        $this->assertFalse(
            $verifier->verify(
                $plaintext,
                $encrypted,
                array('salt' => $salt_diff)
            )
        );
        $encrypted_diff = hash('md5', $salt_diff . $plaintext);
        $this->assertTrue(
            $verifier->verify(
                $plaintext,
                $encrypted_diff,
                array('salt' => $salt_diff)
            )
        );
    }
}

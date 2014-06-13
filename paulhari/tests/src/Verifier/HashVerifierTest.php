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
}

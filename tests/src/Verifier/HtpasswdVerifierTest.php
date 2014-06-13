<?php
namespace Aura\Auth\Verifier;

class HtpasswdVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function testDes()
    {
        $verifier = new HtpasswdVerifier;
        $encrypted = 'ngPfeOKlo3uIs';
        $this->assertTrue($verifier->verify('12345678', $encrypted));
        $this->assertFalse($verifier->verify('wrong', $encrypted));
        $this->assertFalse($verifier->verify('1234567890', $encrypted));
    }

    public function testSha()
    {
        $verifier = new HtpasswdVerifier;
        $encrypted = '{SHA}MCdMR5A70brHYzu/CXQxSeurgF8=';
        $this->assertTrue($verifier->verify('passwd', $encrypted));
        $this->assertFalse($verifier->verify('wrong', $encrypted));
    }

    public function testApr()
    {
        $verifier = new HtpasswdVerifier;
        $encrypted = '$apr1$c4b0dz9t$FRDSRse3FWsZidoPAx9g0.';
        $this->assertTrue($verifier->verify('tkirah', $encrypted));
        $this->assertFalse($verifier->verify('wrong', $encrypted));
    }
}

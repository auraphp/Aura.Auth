<?php
namespace Aura\Auth\Verifier;

class HashVerifier implements VerifierInterface
{
    protected $algo;

    protected $salt;

    public function __construct($algo, $salt = null)
    {
        $this->algo = $algo;
        $this->salt = $salt;
    }

    public function verify($plaintext, $encrypted, array $extra = array())
    {
        return hash($this->algo, $this->salt . $plaintext) === $encrypted;
    }
}

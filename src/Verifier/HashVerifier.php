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

    public function __invoke($plaintext, $encrypted)
    {
        return $this->verifyPassword($plaintext, $encrypted);
    }

    public function verifyPassword($plaintext, $encrypted)
    {
        return hash($this->algo, $this->salt . $plaintext) === $encrypted;
    }
}

<?php
namespace Aura\Auth\Verifier;

class HashVerifier implements PasswordVerifierInterface
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
        return ($this->hashPassword($plaintext) === $encrypted);
    }

    public function hashPassword($plaintext)
    {
        return hash($this->algo, $this->salt . $plaintext);
    }
}

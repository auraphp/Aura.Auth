<?php
namespace Aura\Auth\Verifier;

class PasswordVerifier implements VerifierInterface
{
    protected $algo;

    protected $opts;

    public function __construct($algo, array $opts = array())
    {
        $this->algo = $algo;
        $this->opts = $opts;
    }

    public function verify($plaintext, $encrypted, array $extra = array())
    {
        return password_verify($plaintext, $encrypted);
    }
}

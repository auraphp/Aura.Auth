<?php
namespace Aura\Auth\Verifier;

class PasswordVerifier
{
    protected $algo;

    protected $opts;

    public function __construct($algo, array $opts = array())
    {
        $this->algo = $algo;
        $this->opts = $opts;
    }

    public function __invoke($plaintext, $encrypted)
    {
        return password_verify($encrypted, password_hash(
            $plaintext,
            $this->algo,
            $this->opts
        ));
    }
}

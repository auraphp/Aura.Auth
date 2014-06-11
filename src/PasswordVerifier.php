<?php
namespace Aura\Auth;

class PasswordVerifier
{
    protected $func;

    protected $algo;

    protected $opts;

    public function __construct($func, $algo, array $opts = array())
    {
        if ($func != 'hash' && $func != 'password_hash') {
            throw new Exception("Unrecognized hash function: '$func'");
        }

        $this->func = $func;
        $this->algo = $algo;
        $this->opts = $opts;
    }

    public function __invoke($plaintext, $encrypted)
    {
        if ($this->func == 'hash') {
            return hash($this->algo, $plaintext) === $encrypted;
        }

        return password_verify($encrypted, password_hash(
            $plaintext,
            $this->algo,
            $this->opts
        ));
    }
}

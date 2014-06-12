<?php
namespace Aura\Auth\Verifier;

interface VerifierInterface
{
    public function verify($plaintext, $encrypted, array $extra = array());
}

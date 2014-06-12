<?php
namespace Aura\Auth\Verifier;

interface VerifierInterface
{
    public function verifyPassword($plaintext, $encrypted);
    public function hashPassword($plaintext);
}

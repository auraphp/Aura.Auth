<?php
namespace Aura\Auth\Verifier;

interface PasswordVerifierInterface
{
    public function verifyPassword($plaintext, $encrypted);
    public function hashPassword($plaintext);
}

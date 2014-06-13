<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier;
use PDO;

class AdapterFactory
{
    public function newPdoInstance(
        PDO $pdo,
        $verifier_spec,
        array $cols,
        $from,
        $where = null
    ) {
        if (is_string($verifier_spec)) {
            $verifier = new Verifier\HashVerifier($verifier_spec);
        } elseif (is_int($verifier_spec)) {
            $verifier = new Verifier\PasswordVerifier($verifier_spec);
        } else {
            $verifier = $verifier_spec;
        }

        return new PdoAdapter(
            $pdo,
            $verifier,
            $cols,
            $from,
            $where
        );
    }

    public function newHtpasswdInstance($file)
    {
        $verifier = new Verifier\HtpasswdVerifier;
        return new HtpasswdAdapter(
            $file,
            $verifier
        );
    }
}

<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\AdapterInterface;
use PDO;

class AuthFactory
{
    public function newInstance(AdapterInterface $adapter)
    {
        return new Auth(
            $adapter,
            new Session('Aura\Auth\Auth'),
            new Timer
        );
    }

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
        } elseif {
            $verifier = $verifier_spec;
        }

        return $this->newInstance(new Adapter\PdoAdapter(
            $pdo,
            $verifier,
            $cols,
            $from,
            $where
        ));
    }

    public function newHtpasswdInstance($file)
    {
        $verifier = new Verifier\HtpasswdVerifier;
        return $this->newInstance(new Adapter\HtpassdAdapter(
            $file,
            $verifier
        ));
    }
}

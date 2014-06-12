<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\AdapterInterface;
use PDO;

class AuthFactory
{
    protected $globals;

    public function __construct(&$globals)
    {
        $this->globals = &$globals;
    }

    public function newInstance(AdapterInterface $adapter)
    {
        if (! isset($this->globals['_SESSION'])) {
            throw new Exception('Cannot instantiate without $_SESSION.');
        }

        $segment = 'Aura\Auth\Auth';
        if (! isset($this->globals['_SESSION'][$segment])) {
            $this->globals['_SESSION'][$segment] = array();
        }

        return new Auth(
            $adapter,
            new Session\SessionArray($this->globals['_SESSION'][$segment]),
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
        } else {
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
        return $this->newInstance(new Adapter\HtpasswdAdapter(
            $file,
            $verifier
        ));
    }
}

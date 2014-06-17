<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\Verifier;
use PDO;

/**
 *
 * Adapter Factory
 *
 * @package Aura.Auth
 *
 */
class AdapterFactory
{
    /**
     *
     * @param PDO $pdo
     *
     * @param string/object $verifier_spec
     *
     * @param array $cols
     *
     * @param string $from
     *
     * @param string $where
     *
     * @return Pdoadapter
     *
     */
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

    /**
     *
     * Create an instance of Htpasswd
     *
     * @param string $file
     *
     * @return void
     *
     */
    public function newHtpasswdInstance($file)
    {
        $verifier = new Verifier\HtpasswdVerifier;
        return new HtpasswdAdapter(
            $file,
            $verifier
        );
    }
}

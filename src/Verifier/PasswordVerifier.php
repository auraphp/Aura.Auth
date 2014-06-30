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
namespace Aura\Auth\Verifier;

/**
 *
 * Htaccess password Verifier
 *
 * @package Aura.Auth
 *
 */
class PasswordVerifier implements VerifierInterface
{
    /**
     *
     * @var string
     *
     */
    protected $algo;

    /**
     *
     * @var array
     *
     */
    protected $opts;

    /**
     *
     * Constructor
     *
     * @param string $algo
     *
     */
    public function __construct($algo)
    {
        $this->algo = $algo;
    }

    /**
     *
     * @param string $plaintext Plaintext
     *
     * @param string $hashvalue encrypted string
     *
     * @param array $extra Optional array if used by verify
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = array())
    {
        return password_verify($plaintext, $hashvalue);
    }
}

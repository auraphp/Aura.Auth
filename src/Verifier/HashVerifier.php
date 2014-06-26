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
 * Password Hash Verifier
 *
 * @package Aura.Auth
 *
 */
class HashVerifier implements VerifierInterface
{
    /**
     *
     * @var string
     *
     */
    protected $algo;

    /**
     *
     * @var string
     *
     */
    protected $salt;

    /**
     *
     * @param string $algo
     *
     * @param string $salt
     *
     */
    public function __construct($algo, $salt = null)
    {
        $this->algo = $algo;
        $this->salt = $salt;
    }

    /**
     *
     * @param string $plaintext Plaintext
     *
     * @param string $encrypted encrypted string
     *
     * @param array $extra Optional array if used by verify
     *
     * @return bool
     *
     */
    public function verify($plaintext, $encrypted, array $extra = array())
    {
        $salt = isset($extra['salt']) ? $extra['salt'] : $this->salt;
        return hash($this->algo, $salt . $plaintext) === $encrypted;
    }
}

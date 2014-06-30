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
     * @param string $encrypted encrypted string
     *
     * @param array $extra Optional array if used by verify
     *
     * @return bool
     *
     */
    public function verify($plaintext, $encrypted, array $extra = array())
    {
        return hash($this->algo, $plaintext) === $encrypted;
    }
}

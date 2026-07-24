<?php
namespace Aura\Auth\Verifier;

/**
 *
 * Records every verify() call, so tests can assert what an adapter asked the
 * verifier to do. Delegates to an inner verifier when given one; otherwise
 * returns a fixed result.
 *
 */
class SpyVerifier implements VerifierInterface
{
    public $calls = array();

    protected $inner;

    protected $result;

    public function __construct(?VerifierInterface $inner = null, $result = false)
    {
        $this->inner = $inner;
        $this->result = $result;
    }

    public function verify($plaintext, $hashvalue, array $extra = array()): bool
    {
        $this->calls[] = array($plaintext, $hashvalue, $extra);

        if ($this->inner) {
            return $this->inner->verify($plaintext, $hashvalue, $extra);
        }

        return $this->result;
    }
}

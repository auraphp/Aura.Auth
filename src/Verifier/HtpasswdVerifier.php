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
class HtpasswdVerifier implements VerifierInterface
{
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
        $hashvalue = trim($hashvalue);

        if (substr($hashvalue, 0, 4) == '$2y$') {
            return $this->bcrypt($plaintext, $hashvalue);
        }

        if (substr($hashvalue, 0, 5) == '{SHA}') {
            return $this->sha($plaintext, $hashvalue);
        }

        if (substr($hashvalue, 0, 6) == '$apr1$') {
            return $this->apr1($plaintext, $hashvalue);
        }

        return $this->des($plaintext, $hashvalue);
    }

    // use SHA1 encryption.  pack SHA binary into hexadecimal,
    // then encode into characters using base64. this is per
    // Tomas V. V. Cox.
    protected function sha($plaintext, $hashvalue)
    {
        $hex = pack('H40', sha1($plaintext));
        $computed_hash = '{SHA}' . base64_encode($hex);
        return $computed_hash === $hashvalue;
    }

    /**
     *
     * APR compatible MD5 encryption.
     *
     * @author Mike Wallner <mike@php.net>
     *
     * @author Paul M. Jones (minor modfications) <pmjones@solarphp.com>
     *
     * @param string $plain Plaintext to crypt.
     *
     * @param string $salt The salt to use for encryption.
     *
     * @return string The APR MD5 encrypted string.
     *
     */
    protected function apr1($plaintext, $hashvalue)
    {
        $salt = preg_replace('/^\$apr1\$([^$]+)\$.*/', '\\1', $hashvalue);
        $context = $this->computeContext($plaintext, $salt);
        $binary = $this->computeBinary($plaintext, $salt, $context);
        $p = $this->computeP($binary);
        $computed_hash = '$apr1$' . $salt . '$' . $p
                       . $this->convert64(ord($binary[11]), 3);
        return $computed_hash === $hashvalue;
    }

    protected function computeContext($plaintext, $salt)
    {
        $length = strlen($plaintext);
        $hash = hash('md5', $plaintext . $salt . $plaintext, true);
        $context = $plaintext . '$apr1$' . $salt;

        for ($i = $length; $i > 0; $i -= 16) {
            $context .= substr($hash, 0, min(16 , $i));
        }

        for ( $i = $length; $i > 0; $i >>= 1) {
            $context .= ($i & 1) ? chr(0) : $plaintext[0];
        }

        return $context;
    }

    protected function computeBinary($plaintext, $salt, $context)
    {
        $binary = hash('md5', $context, true);
        for($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $plaintext : $binary;
            if ($i % 3) {
                $new .= $salt;
            }
            if ($i % 7) {
                $new .= $plaintext;
            }
            $new .= ($i & 1) ? $binary : $plaintext;
            $binary = hash('md5', $new, true);
        }
        return $binary;
    }

    protected function computeP($binary)
    {
        $p = array();
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) {
                $j = 5;
            }
            $p[] = $this->convert64(
                (ord($binary[$i]) << 16) |
                (ord($binary[$k]) << 8) |
                (ord($binary[$j])),
                5
            );
        }
        return implode($p);
    }

    /**
     *
     * Convert to allowed 64 characters for encryption.
     *
     * @author Mike Wallner <mike@php.net>
     *
     * @author Paul M. Jones (minor modfications) <pmjones@solarphp.com>
     *
     * @param string $value The value to convert.
     *
     * @param int $count The number of characters.
     *
     * @return string The converted value.
     *
     */
    protected function convert64($value, $count)
    {
        $charset = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $result = '';
        while (--$count) {
            $result .= $charset[$value & 0x3f];
            $value >>= 6;
        }
        return $result;
    }

    // Note that crypt() will only check up to the first 8
    // characters of a password; chars after 8 are ignored. This
    // means that if the real password is "atecharsnine", the
    // word "atechars" would be valid.  This is bad.  As a
    // workaround, if the password provided by the user is
    // longer than 8 characters, this method will *not* validate
    // it.
    protected function des($plaintext, $hashvalue)
    {
        if (strlen($plaintext) > 8) {
            return false;
        }

        $computed_hash = crypt($plaintext, $hashvalue);
        return $computed_hash === $hashvalue;
    }

    protected function bcrypt($plaintext, $hashvalue)
    {
        return password_verify($plaintext, $hashvalue);
    }
}

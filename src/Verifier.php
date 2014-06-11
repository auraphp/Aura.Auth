<?php
namespace Aura\Auth;

class Verifier
{
    protected $method;

    protected $algo;

    protected $opts;

    public function __construct($method, $algo = null, array $opts = array())
    {
        if (! method_exists($this, $method)) {
            throw new Exception("Unrecognized method: '$method'");
        }
        $this->method = $method;
        $this->algo = $algo;
        $this->opts = $opts;
    }

    public function __invoke($plaintext, $encrypted)
    {
        $method = $this->method;
        return $this->$method($plaintext, $encrypted);
    }

    public function hash($plaintext, $encrypted)
    {
        return hash($this->algo, $plaintext) === $encrypted;
    }

    public function passwordHash($plaintext, $encrypted)
    {
        return password_verify($encrypted, password_hash(
            $plaintext,
            $this->algo,
            $this->opts
        ));
    }

    public function htpasswd($plaintext, $encrypted)
    {
        // what kind of encryption hash are we using?  look at the first
        // few characters of the hash to find out.
        if (substr($encrypted, 0, 6) == '$apr1$') {

            // use the apache-specific MD5 encryption
            $computed_hash = $this->htpasswdApr1($plaintext, $encrypted);

        } elseif (substr($encrypted, 0, 5) == '{SHA}') {

            // use SHA1 encryption.  pack SHA binary into hexadecimal,
            // then encode into characters using base64. this is per
            // Tomas V. V. Cox.
            $hex = pack('H40', sha1($plaintext));
            $computed_hash = '{SHA}' . base64_encode($hex);

        } else {

            // use DES encryption (the default).
            //
            // Note that crypt() will only check up to the first 8
            // characters of a password; chars after 8 are ignored. This
            // means that if the real password is "atecharsnine", the
            // word "atechars" would be valid.  This is bad.  As a
            // workaround, if the password provided by the user is
            // longer than 8 characters, this method will *not* validate
            // it.
            //
            // is the password longer than 8 characters?
            if (strlen($plaintext) > 8) {
                // automatically reject
                return false;
            } else {
                $computed_hash = crypt($plaintext, $encrypted);
            }
        }

        // did the hashes match?
        return $encrypted == $computed_hash;
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
    protected function htpasswdApr1($plain, $salt)
    {
        if (preg_match('/^\$apr1\$/', $salt)) {
            $salt = preg_replace('/^\$apr1\$([^$]+)\$.*/', '\\1', $salt);
        } else {
            $salt = substr($salt, 0,8);
        }

        $length  = strlen($plain);
        $context = $plain . '$apr1$' . $salt;

        $binary = hash('md5', $plain . $salt . $plain, true);

        for ($i = $length; $i > 0; $i -= 16) {
            $context .= substr($binary, 0, min(16 , $i));
        }
        for ( $i = $length; $i > 0; $i >>= 1) {
            $context .= ($i & 1) ? chr(0) : $plain[0];
        }

        $binary = hash('md5', $context, true);

        for($i = 0; $i < 1000; $i++) {
            $new = ($i & 1) ? $plain : $binary;
            if ($i % 3) {
                $new .= $salt;
            }
            if ($i % 7) {
                $new .= $plain;
            }
            $new .= ($i & 1) ? $binary : $plain;
            $binary = hash('md5', $new, true);
        }

        $p = array();
        for ($i = 0; $i < 5; $i++) {
            $k = $i + 6;
            $j = $i + 12;
            if ($j == 16) {
                $j = 5;
            }
            $p[] = $this->_64(
                (ord($binary[$i]) << 16) |
                (ord($binary[$k]) << 8) |
                (ord($binary[$j])),
                5
            );
        }

        return '$apr1$' . $salt . '$' . implode($p)
             . $this->htpasswd64(ord($binary[11]), 3);
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
    protected function htpasswd64($value, $count)
    {
        $charset = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $result = '';
        while (--$count) {
            $result .= $charset[$value & 0x3f];
            $value >>= 6;
        }
        return $result;
    }
}

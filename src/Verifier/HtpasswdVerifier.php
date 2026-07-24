<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Verifier;

/**
 *
 * Verfies passwords from htpasswd files; supports APR1/MD5, DES, SHA1, and
 * Bcrypt.
 *
 * The APR1/MD5 implementation was originally written by Mike Wallner
 * <mike@php.net>; any flaws are the fault of Paul M. Jones
 * <pmjones88@gmail.com>.
 *
 * @package Aura.Auth
 *
 */
class HtpasswdVerifier implements VerifierInterface, DummyHashInterface
{
    /**
     *
     * The salt alphabet crypt() accepts.
     *
     * @const string
     *
     */
    const SALT_CHARS = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     *
     * Which format the dummy hash should imitate; see getDummyHash().
     *
     * @var string
     *
     */
    protected $dummy_format;

    /**
     *
     * A memoised throwaway hash in that format.
     *
     * @var string|null
     *
     */
    protected $dummy_hash;

    /**
     *
     * Constructor.
     *
     * @param string $dummy_format Which of the four htpasswd formats this
     * file mostly holds -- `apr1` (the default `htpasswd` produces, and the
     * default here), `bcrypt` (`htpasswd -B`), `sha` (`htpasswd -s`), or `des`
     * (`htpasswd -d`). It affects nothing but getDummyHash(): verify() still
     * dispatches per entry, so a file mixing formats still authenticates
     * everyone. Set it to whatever the bulk of the file holds, because it is
     * the cost of the *typical* entry that the unknown-username path has to
     * match.
     *
     */
    public function __construct($dummy_format = 'apr1')
    {
        $this->dummy_format = $dummy_format;
    }

    /**
     *
     * Returns a throwaway hash in the configured htpasswd format, so that an
     * adapter's "no such username" path costs what a real failed verification
     * costs.
     *
     * This matters more here than for PasswordVerifier, because three of the
     * four htpasswd formats verify in microseconds. Falling back to the
     * adapter's bcrypt constant against an `$apr1$` or `{SHA}` file does not
     * equalise the two paths, it inverts them by a far wider margin than the
     * leak it was meant to close.
     *
     * The plaintext is random and never recorded, so nothing verifies against
     * the result; it is memoised so the failure path pays for one verification
     * rather than two.
     *
     * @return string
     *
     */
    public function getDummyHash(): string
    {
        if ($this->dummy_hash !== null) {
            return $this->dummy_hash;
        }

        $unknown = bin2hex(random_bytes(32));

        switch ($this->dummy_format) {
            case 'bcrypt':
                $this->dummy_hash = password_hash($unknown, PASSWORD_BCRYPT);
                break;
            case 'sha':
                $this->dummy_hash = '{SHA}' . base64_encode(sha1($unknown, true));
                break;
            case 'des':
                // DES reads only the first 8 characters, which is the whole
                // reason this format is discouraged; the dummy is short enough
                // to take the same path a real DES entry takes
                $this->dummy_hash = crypt(
                    substr($unknown, 0, 8),
                    $this->salt(2)
                );
                break;
            case 'apr1':
            default:
                $this->dummy_hash = $this->computeApr1($unknown, $this->salt(8));
                break;
        }

        return $this->dummy_hash;
    }

    /**
     *
     * Returns random salt characters from crypt()'s accepted alphabet.
     *
     * @param int $length How many characters.
     *
     * @return string
     *
     */
    protected function salt($length): string
    {
        $salt = '';
        $max = strlen(self::SALT_CHARS) - 1;
        for ($i = 0; $i < $length; $i ++) {
            $salt .= self::SALT_CHARS[random_int(0, $max)];
        }

        return $salt;
    }

    /**
     *
     * Verifies a plaintext password against a hash.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @param array $extra Optional array if used by verify.
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = array()): bool
    {
        $hashvalue = trim($hashvalue);

        if (substr($hashvalue, 0, 4) == '$2y$') {
            return password_verify($plaintext, $hashvalue);
        }

        if (substr($hashvalue, 0, 5) == '{SHA}') {
            return $this->sha($plaintext, $hashvalue);
        }

        if (substr($hashvalue, 0, 6) == '$apr1$') {
            return $this->apr1($plaintext, $hashvalue);
        }

        return $this->des($plaintext, $hashvalue);
    }

    /**
     *
     * Verify using SHA1 hashing.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @return bool
     *
     */
    protected function sha($plaintext, $hashvalue): bool
    {
        $hex = sha1($plaintext, true);
        $computed_hash = '{SHA}' . base64_encode($hex);
        return hash_equals($hashvalue, $computed_hash);
    }

    /**
     *
     * Verify using APR compatible MD5 hashing.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @return bool
     *
     */
    protected function apr1($plaintext, $hashvalue): bool
    {
        $salt = preg_replace('/^\$apr1\$([^$]+)\$.*/', '\\1', $hashvalue);
        return hash_equals($hashvalue, $this->computeApr1($plaintext, $salt));
    }

    /**
     *
     * Computes an APR1/MD5 hash from a plaintext and a salt.
     *
     * Split out of apr1() so that getDummyHash() can produce a real one.
     * PHP's crypt() has no `$apr1$` support -- it answers `*0` for that salt
     * format -- so a dummy cannot be generated the way the crypt-based formats
     * are; it has to come from this implementation, the same one verification
     * uses, which is what makes the two cost the same.
     *
     * @param string $plaintext The plaintext password.
     *
     * @param string $salt The salt, without the surrounding `$apr1$...$`.
     *
     * @return string
     *
     */
    protected function computeApr1($plaintext, $salt): string
    {
        $context = $this->computeContext($plaintext, $salt);
        $binary = $this->computeBinary($plaintext, $salt, $context);
        $p = $this->computeP($binary);

        return '$apr1$' . $salt . '$' . $p
             . $this->convert64(ord($binary[11]), 3);
    }

    /**
     *
     * Compute the context.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $salt The salt.
     *
     * @return string
     *
     */
    protected function computeContext($plaintext, $salt): string
    {
        $length = strlen($plaintext);
        $hash = hash('md5', $plaintext . $salt . $plaintext, true);
        $context = $plaintext . '$apr1$' . $salt;

        for ($i = $length; $i > 0; $i -= 16) {
            $context .= substr($hash, 0, min(16, $i));
        }

        for ($i = $length; $i > 0; $i >>= 1) {
            $context .= ($i & 1) ? chr(0) : $plaintext[0];
        }

        return $context;
    }

    /**
     *
     * Compute the binary.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $salt The salt.
     *
     * @param string $context The context.
     *
     * @return string
     *
     */
    protected function computeBinary($plaintext, $salt, $context): string
    {
        $binary = hash('md5', $context, true);
        for ($i = 0; $i < 1000; $i++) {
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

    /**
     *
     * Compute the P value for a binary.
     *
     * @param string $binary The binary.
     *
     * @return string
     *
     */
    protected function computeP($binary): string
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
     * @param string $value The value to convert.
     *
     * @param int $count The number of characters.
     *
     * @return string The converted value.
     *
     */
    protected function convert64($value, $count): string
    {
        $charset = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $result = '';
        while (--$count) {
            $result .= $charset[$value & 0x3f];
            $value >>= 6;
        }
        return $result;
    }

    /**
     *
     * Verify using DES hashing.
     *
     * Note that crypt() will only check up to the first 8
     * characters of a password; chars after 8 are ignored. This
     * means that if the real password is "atecharsnine", the
     * word "atechars" would be valid.  This is bad.  As a
     * workaround, if the password provided by the user is
     * longer than 8 characters, this method will *not* verify
     * it.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Comparison hash.
     *
     * @return bool
     *
     */
    protected function des($plaintext, $hashvalue): bool
    {
        if (strlen($plaintext) > 8) {
            return false;
        }

        $computed_hash = crypt($plaintext, $hashvalue);
        return hash_equals($hashvalue, $computed_hash);
    }
}

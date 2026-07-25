<?php
namespace Aura\Auth\Verifier;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The unknown-username path verifies against a dummy hash so that it costs
 * what a wrong password costs. That only holds if the dummy is in the format
 * the verifier actually reads: a bcrypt dummy in front of an `$apr1$` file or
 * a legacy `hash()` column does not close the gap, it inverts it -- the
 * unknown username becomes the slow answer, by a wider margin than the leak
 * being fixed.
 *
 * These tests assert the format matches, which is deterministic; the timing
 * test at the bottom checks the consequence directly but loosely.
 */
class DummyHashTest extends \PHPUnit\Framework\TestCase
{
    public static function verifierProvider()
    {
        return array(
            'bcrypt' => array(
                new PasswordVerifier(PASSWORD_BCRYPT),
                password_hash('the real password', PASSWORD_BCRYPT),
            ),
            'bcrypt at a pinned cost' => array(
                new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 5)),
                password_hash('the real password', PASSWORD_BCRYPT, array('cost' => 5)),
            ),
            'argon2id' => array(
                new PasswordVerifier(PASSWORD_ARGON2ID),
                password_hash('the real password', PASSWORD_ARGON2ID),
            ),
            'legacy hash() algo' => array(
                new PasswordVerifier('sha256'),
                hash('sha256', 'the real password'),
            ),
            'htpasswd apr1' => array(
                new HtpasswdVerifier('apr1'),
                // Apache htpasswd -nbm testuser myPassword
                '$apr1$5fWRDtKU$zJGoDMoovZiKUES4K8Bol1',
            ),
            'htpasswd sha' => array(
                new HtpasswdVerifier('sha'),
                '{SHA}' . base64_encode(sha1('the real password', true)),
            ),
            'htpasswd bcrypt' => array(
                new HtpasswdVerifier('bcrypt'),
                password_hash('the real password', PASSWORD_BCRYPT),
            ),
            'htpasswd bcrypt at a pinned cost' => array(
                new HtpasswdVerifier('bcrypt', array('cost' => 5)),
                password_hash('the real password', PASSWORD_BCRYPT, array('cost' => 5)),
            ),
            'htpasswd des' => array(
                new HtpasswdVerifier('des'),
                crypt('realpw', 'ab'),
            ),
        );
    }

    /**
     * The dummy has to reach the same branch of verify() that a stored hash
     * reaches, which is decided by its format. Comparing the leading marker
     * covers every case here: password_hash() output carries its algorithm,
     * the htpasswd formats are prefix-dispatched, and the two unprefixed ones
     * (legacy digest, DES) are matched on length.
     */
    #[DataProvider('verifierProvider')]
    public function testDummyHashIsInTheSameFormatAsAStoredHash($verifier, $stored)
    {
        $dummy = $verifier->getDummyHash();

        $this->assertSame(
            $this->formatOf($stored),
            $this->formatOf($dummy),
            'the dummy is not in the format this verifier reads, so the'
                . ' unknown-username path will not cost what a wrong password costs'
        );
    }

    /**
     * A dummy nothing can verify against is the whole safety property: the
     * value may be logged, copied, or published without becoming a password.
     */
    #[DataProvider('verifierProvider')]
    public function testNothingVerifiesAgainstTheDummyHash($verifier, $stored)
    {
        $dummy = $verifier->getDummyHash();

        foreach (array('', 'password', 'dummy', 'the real password', $dummy) as $guess) {
            $this->assertFalse(
                $verifier->verify($guess, $dummy),
                "'{$guess}' verifies against the dummy hash"
            );
        }
    }

    /**
     * Generating a fresh hash per call would make the failure path pay for two
     * verifications instead of one, reopening the difference from the other
     * side.
     */
    #[DataProvider('verifierProvider')]
    public function testDummyHashIsMemoised($verifier, $stored)
    {
        $this->assertSame($verifier->getDummyHash(), $verifier->getDummyHash());
    }

    /**
     * Two verifiers must not share a dummy: it is generated from random_bytes()
     * per instance, so a repeated value would mean the randomness is not being
     * drawn.
     */
    public function testDummyHashDiffersBetweenInstances()
    {
        $one = new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 4));
        $two = new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 4));

        $this->assertNotSame($one->getDummyHash(), $two->getDummyHash());
    }

    /**
     * The apr1 dummy cannot be made with crypt() -- PHP has no `$apr1$` support
     * and answers `*0` -- so it comes from the package's own implementation.
     * Check that implementation against Apache's actual output, otherwise a
     * malformed dummy would fall through verify() to the DES branch and cost
     * the wrong amount while still looking plausible.
     */
    public function testApr1ImplementationMatchesApacheHtpasswd()
    {
        $verifier = new HtpasswdVerifier('apr1');

        // Apache htpasswd -nbm testuser myPassword
        $apache = '$apr1$5fWRDtKU$zJGoDMoovZiKUES4K8Bol1';

        $this->assertTrue($verifier->verify('myPassword', $apache));
        $this->assertFalse($verifier->verify('wrongPassword', $apache));
        $this->assertMatchesRegularExpression(
            '/^\$apr1\$[.\/0-9A-Za-z]{8}\$[.\/0-9A-Za-z]{22}$/',
            $verifier->getDummyHash()
        );
    }

    /**
     * The same cases, at the cheapest settings that still exercise each
     * branch. The property under test is the *ratio* between the two paths,
     * which does not depend on the work factor -- and running the real one
     * (bcrypt cost 12, argon2id) twenty times per case put half a minute on
     * the suite for no extra coverage.
     */
    public static function timingProvider()
    {
        return array(
            'bcrypt' => array(
                new PasswordVerifier(PASSWORD_BCRYPT, array('cost' => 4)),
                password_hash('the real password', PASSWORD_BCRYPT, array('cost' => 4)),
            ),
            'legacy hash() algo' => array(
                new PasswordVerifier('sha256'),
                hash('sha256', 'the real password'),
            ),
            'htpasswd apr1' => array(
                new HtpasswdVerifier('apr1'),
                '$apr1$5fWRDtKU$zJGoDMoovZiKUES4K8Bol1',
            ),
            'htpasswd sha' => array(
                new HtpasswdVerifier('sha'),
                '{SHA}' . base64_encode(sha1('the real password', true)),
            ),
            'htpasswd bcrypt' => array(
                new HtpasswdVerifier('bcrypt', array('cost' => 4)),
                password_hash('the real password', PASSWORD_BCRYPT, array('cost' => 4)),
            ),
            'htpasswd des' => array(
                new HtpasswdVerifier('des'),
                crypt('realpw', 'ab'),
            ),
        );
    }

    /**
     * A `$2y$` hash encodes its cost, so matching the format is not enough on
     * its own: `htpasswd -B` writes cost 5 by default while PHP writes 10, or
     * 12 from 8.4 on, and a dummy left at PHP's default would cost an unknown
     * username roughly 128 times what a wrong password costs.
     */
    public function testHtpasswdBcryptDummyHonoursTheConfiguredCost()
    {
        $verifier = new HtpasswdVerifier('bcrypt', array('cost' => 5));

        $info = password_get_info($verifier->getDummyHash());

        $this->assertSame(5, $info['options']['cost']);
    }

    /**
     * The point of all of it. Bounds are deliberately wide -- this measures
     * wall-clock on whatever machine CI gave us -- because the failure being
     * guarded against is three to six orders of magnitude, not a few percent.
     */
    #[DataProvider('timingProvider')]
    public function testBothPathsCostAboutTheSame($verifier, $stored)
    {
        $dummy = $verifier->getDummyHash();

        $known = $this->timeOf(fn() => $verifier->verify('wrong password', $stored));
        $unknown = $this->timeOf(fn() => $verifier->verify('wrong password', $dummy));

        $ratio = $unknown / max($known, 1e-9);

        $this->assertGreaterThan(
            0.1,
            $ratio,
            sprintf('unknown username is %.0fx faster than a wrong password', 1 / $ratio)
        );
        $this->assertLessThan(
            10.0,
            $ratio,
            sprintf('unknown username is %.0fx slower than a wrong password', $ratio)
        );
    }

    /**
     * Seconds per call, averaged, with one untimed call first so that a
     * memoised dummy is already built.
     */
    protected function timeOf(callable $call): float
    {
        $call();

        // enough iterations that the microsecond formats are measurable, few
        // enough that the bcrypt ones do not dominate the suite
        $iterations = 20;

        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i ++) {
            $call();
        }

        return (hrtime(true) - $start) / 1e9 / $iterations;
    }

    /**
     * A stable label for "which branch of verify() does this hash take".
     */
    protected function formatOf($hashvalue): string
    {
        $info = password_get_info($hashvalue);
        if ($info['algo'] !== null) {
            return 'password_hash:' . $info['algoName']
                 . ':' . ($info['options']['cost'] ?? '-');
        }

        foreach (array('{SHA}', '$apr1$') as $prefix) {
            if (str_starts_with($hashvalue, $prefix)) {
                return 'htpasswd:' . trim($prefix, '${}');
            }
        }

        return 'unprefixed:' . strlen($hashvalue);
    }
}

<?php
namespace Aura\Auth\Session;

use Aura\Auth\Auth;
use Aura\Auth\Status;

class ArraySegmentTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAndSet()
    {
        $segment = new ArraySegment;
        $this->assertNull($segment->get('nope'));
        $this->assertSame('alt', $segment->get('nope', 'alt'));

        $segment->set('key', 'val');
        $this->assertSame('val', $segment->get('key'));
    }

    public function testConstructorSeedsValues()
    {
        $segment = new ArraySegment(array('key' => 'val'));
        $this->assertSame('val', $segment->get('key'));
    }

    public function testStoredNullIsDistinctFromMissing()
    {
        $segment = new ArraySegment;
        $segment->set('key', null);

        // a stored null must not fall back to the alternative
        $this->assertNull($segment->get('key', 'alt'));
        $this->assertSame('alt', $segment->get('other', 'alt'));
    }

    public function testAuthRoundTripsWithoutASession()
    {
        // the point of the class: Segment::set() discards writes when there is
        // no $_SESSION, so an authenticated user would read back as anonymous
        // within the same request. This must not.
        $this->assertFalse(isset($_SESSION));

        $auth = new Auth(new ArraySegment);
        $auth->set(Status::VALID, 1, 2, 'boshag', array('foo' => 'bar'));

        $this->assertSame(Status::VALID, $auth->getStatus());
        $this->assertSame('boshag', $auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $auth->getUserData());
        $this->assertTrue($auth->isValid());
        $this->assertFalse($auth->isAnon());
    }

    public function testFreshAuthIsAnonymous()
    {
        $auth = new Auth(new ArraySegment);
        $this->assertTrue($auth->isAnon());
    }
}

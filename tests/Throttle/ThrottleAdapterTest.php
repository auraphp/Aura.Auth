<?php
namespace Aura\Auth\Throttle;

use Aura\Auth\Adapter\AbstractAdapter;
use Aura\Auth\Adapter\ThrottleAdapter;
use Aura\Auth\Exception\PasswordIncorrect;
use Aura\Auth\Exception\ThrottleExceeded;

/**
 * An inner adapter that succeeds only for password "good", and records how many
 * times its login() was actually reached.
 */
class StubInnerAdapter extends AbstractAdapter
{
    public $calls = 0;

    public function login(array $input): array
    {
        $this->calls++;
        if (($input['password'] ?? '') !== 'good') {
            throw new PasswordIncorrect();
        }
        return array($input['username'], array('role' => 'user'));
    }
}

class ThrottleAdapterTest extends \PHPUnit\Framework\TestCase
{
    protected $inner;

    protected $storage;

    protected $adapter;

    protected function setUp() : void
    {
        $this->inner = new StubInnerAdapter();
        $this->storage = new FakeThrottleStorage();
        $throttle = new ThrottleService(
            $this->storage,
            array('max_attempts' => 3, 'cap' => 60)
        );
        $this->adapter = new ThrottleAdapter($this->inner, $throttle);
    }

    public function testSuccessReturnsInnerResult()
    {
        $result = $this->adapter->login(array('username' => 'bob', 'password' => 'good'));
        $this->assertSame(array('bob', array('role' => 'user')), $result);
    }

    public function testFailureIsRecorded()
    {
        try {
            $this->adapter->login(array('username' => 'bob', 'password' => 'bad'));
            $this->fail('Expected PasswordIncorrect');
        } catch (PasswordIncorrect $e) {
            // expected
        }

        $this->assertSame(1, $this->storage->getFailures('bob')['count']);
    }

    public function testSuccessClearsFailures()
    {
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob');

        $this->adapter->login(array('username' => 'bob', 'password' => 'good'));

        $this->assertSame(0, $this->storage->getFailures('bob')['count']);
    }

    public function testBlocksBeforeReachingInnerAdapter()
    {
        // exceed the threshold with fresh failures
        $now = time();
        $this->storage->failures['bob'] = array($now, $now, $now, $now);
        $this->inner->calls = 0;

        try {
            $this->adapter->login(array('username' => 'bob', 'password' => 'good'));
            $this->fail('Expected ThrottleExceeded');
        } catch (ThrottleExceeded $e) {
            // the inner adapter must never be reached while blocked
            $this->assertSame(0, $this->inner->calls);
        }
    }
}

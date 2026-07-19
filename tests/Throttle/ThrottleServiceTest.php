<?php
namespace Aura\Auth\Throttle;

use Aura\Auth\Exception\ThrottleExceeded;

class ThrottleServiceTest extends \PHPUnit\Framework\TestCase
{
    protected $storage;

    protected $service;

    protected function setUp() : void
    {
        $this->storage = new FakeThrottleStorage();
        // small, clear numbers: 3 free attempts, cap the backoff at 60s
        $this->service = new ThrottleService(
            $this->storage,
            array('max_attempts' => 3, 'cap' => 60)
        );
    }

    public function testUnderThresholdAllows()
    {
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob'); // count == max_attempts

        $this->service->assert('bob'); // no throw
        $this->assertTrue(true);
    }

    public function testOverThresholdThrows()
    {
        // 4 failures just now: wait = 2 ^ (4 - 3) = 2s, elapsed ~0
        $now = time();
        $this->storage->failures['bob'] = array($now, $now, $now, $now);

        try {
            $this->service->assert('bob');
            $this->fail('Expected ThrottleExceeded');
        } catch (ThrottleExceeded $e) {
            $this->assertGreaterThan(0, $e->getSecondsRemaining());
            $this->assertLessThanOrEqual(2, $e->getSecondsRemaining());
        }
    }

    public function testBackoffCapApplies()
    {
        // 20 failures now: 2^17 would be huge, so it must be capped at 60
        $now = time();
        $this->storage->failures['bob'] = array_fill(0, 20, $now);

        try {
            $this->service->assert('bob');
            $this->fail('Expected ThrottleExceeded');
        } catch (ThrottleExceeded $e) {
            $this->assertLessThanOrEqual(60, $e->getSecondsRemaining());
        }
    }

    public function testAllowsOnceEnoughTimeHasPassed()
    {
        // 4 failures, but the most recent was 30s ago; wait is only 2s
        $past = time() - 30;
        $this->storage->failures['bob'] = array($past, $past, $past, $past);

        $this->service->assert('bob'); // no throw
        $this->assertTrue(true);
    }

    public function testResetClearsBackoff()
    {
        $now = time();
        $this->storage->failures['bob'] = array($now, $now, $now, $now);

        $this->service->reset('bob');

        $this->service->assert('bob'); // no throw
        $this->assertTrue(true);
    }

    public function testRecordFailureDelegatesToStorage()
    {
        $this->service->recordFailure('bob');
        $this->assertSame(1, $this->storage->getFailures('bob')['count']);
    }
}

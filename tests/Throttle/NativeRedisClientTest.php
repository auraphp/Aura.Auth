<?php
namespace Aura\Auth\Throttle;

class NativeRedisClientTest extends \PHPUnit\Framework\TestCase
{
    protected $redis;

    protected $client;

    protected function setUp() : void
    {
        // FakeRedis stands in for a phpredis \Redis / Predis\Client, exposing
        // the lowercase command methods the adapter calls.
        $this->redis = new FakeRedis();
        $this->client = new NativeRedisClient($this->redis);
    }

    public function testHashIncrement()
    {
        $this->assertSame(1, $this->client->hashIncrement('k', 'count', 1));
        $this->assertSame(3, $this->client->hashIncrement('k', 'count', 2));
        $this->assertSame('3', $this->redis->hashes['k']['count']);
    }

    public function testHashSetAndGetAll()
    {
        $this->client->hashSet('k', 'last', '12345');
        $this->assertSame(array('last' => '12345'), $this->client->hashGetAll('k'));
    }

    public function testHashGetAllMissingReturnsEmptyArray()
    {
        $this->assertSame(array(), $this->client->hashGetAll('nope'));
    }

    public function testExpire()
    {
        $this->client->hashSet('k', 'last', '1');
        $this->client->expire('k', 300);
        $this->assertSame(300, $this->redis->expires['k']);
    }

    public function testDelete()
    {
        $this->client->hashSet('k', 'last', '1');
        $this->client->delete('k');
        $this->assertSame(array(), $this->client->hashGetAll('k'));
    }

    /**
     * A read that fails must not be reported as a count of zero: the caller
     * cannot distinguish that from "no failures recorded" and would let every
     * attempt through, switching throttling off for the key with nothing said.
     */
    public function testFailedReadThrowsRatherThanReportingZeroFailures()
    {
        $this->redis->fail = array('hgetall');
        $this->redis->last_error = 'WRONGTYPE Operation against a key holding the wrong kind of value';

        $this->expectException('Aura\Auth\Exception\ConnectionFailed');
        $this->client->hashGetAll('k');
    }

    /**
     * The same read failure seen through the storage: getFailures() would
     * otherwise answer count 0, and ThrottleService::assert() would allow an
     * unlimited number of attempts.
     */
    public function testFailedReadDoesNotSilentlyAllowUnlimitedAttempts()
    {
        $storage = new RedisThrottleStorage($this->client);
        $this->redis->fail = array('hgetall');

        $this->expectException('Aura\Auth\Exception\ConnectionFailed');
        $storage->getFailures('bob');
    }

    /**
     * An uncounted failure is a free guess; the attempt must error rather than
     * pass unrecorded.
     */
    public function testFailedIncrementThrowsRatherThanLosingTheFailedAttempt()
    {
        $this->redis->fail = array('hincrby');

        $this->expectException('Aura\Auth\Exception\ConnectionFailed');
        $this->client->hashIncrement('k', 'count', 1);
    }

    /**
     * Without the 'last' stamp, ThrottleService computes backoff against a
     * null timestamp, which reads as "no wait required".
     */
    public function testFailedStampThrowsRatherThanLeavingBackoffUncomputable()
    {
        $this->redis->fail = array('hset');

        $this->expectException('Aura\Auth\Exception\ConnectionFailed');
        $this->client->hashSet('k', 'last', '1');
    }

    /**
     * The guard keys on an exact false, because these commands answer 0 on
     * perfectly ordinary outcomes -- HSET updating an existing field, DEL
     * finding nothing to remove. A looser check would throw on healthy
     * traffic, locking users out of a working system.
     */
    public function testOrdinaryZeroRepliesAreNotTreatedAsFailure()
    {
        $this->client->hashSet('k', 'last', '1');

        // updating an existing field: HSET answers 0
        $this->client->hashSet('k', 'last', '2');
        $this->assertSame(array('last' => '2'), $this->client->hashGetAll('k'));

        // nothing to delete: DEL answers 0
        $this->client->delete('absent');
        $this->assertSame(array(), $this->client->hashGetAll('absent'));
    }

    /**
     * EXPIRE answers false for a key that no longer exists, which happens
     * normally when a reset or a TTL lands between the increment and the
     * expiry call. That is not a failure and must not throw.
     */
    public function testExpireOnAMissingKeyDoesNotThrow()
    {
        $this->redis->fail = array('expire');

        $this->client->expire('gone', 300);
        $this->assertTrue(true);
    }

    /**
     * The server's own explanation is the thing an operator needs to fix a
     * WRONGTYPE, so it should survive into the message.
     */
    public function testFailureMessageCarriesTheRedisError()
    {
        $this->redis->fail = array('hgetall');
        $this->redis->last_error = 'WRONGTYPE Operation against a key holding the wrong kind of value';

        try {
            $this->client->hashGetAll('k');
            $this->fail('expected ConnectionFailed');
        } catch (\Aura\Auth\Exception\ConnectionFailed $e) {
            $this->assertStringContainsString(
                'WRONGTYPE Operation against a key holding the wrong kind of value',
                $e->getMessage()
            );
            $this->assertStringContainsString("key 'k'", $e->getMessage());
        }
    }
}

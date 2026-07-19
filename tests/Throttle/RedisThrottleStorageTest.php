<?php
namespace Aura\Auth\Throttle;

class RedisThrottleStorageTest extends \PHPUnit\Framework\TestCase
{
    protected $client;

    protected $storage;

    protected function setUp() : void
    {
        $this->client = new FakeRedisClient();
        $this->storage = new RedisThrottleStorage($this->client);
    }

    public function testNoFailures()
    {
        $this->assertSame(
            array('count' => 0, 'last' => null),
            $this->storage->getFailures('bob')
        );
    }

    public function testRecordAndCount()
    {
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('alice');

        $bob = $this->storage->getFailures('bob');
        $this->assertSame(2, $bob['count']);
        $this->assertNotNull($bob['last']);
        $this->assertLessThanOrEqual(time(), $bob['last']);

        $this->assertSame(1, $this->storage->getFailures('alice')['count']);
    }

    public function testRecordSetsPrefixedKeyAndTtl()
    {
        $storage = new RedisThrottleStorage($this->client, 'throttle:', 300);
        $storage->recordFailure('bob');

        $this->assertArrayHasKey('throttle:bob', $this->client->hashes);
        $this->assertSame(300, $this->client->expires['throttle:bob']);
    }

    public function testReset()
    {
        $this->storage->recordFailure('bob');
        $this->storage->recordFailure('bob');
        $this->storage->reset('bob');

        $this->assertSame(0, $this->storage->getFailures('bob')['count']);
    }

    public function testDeleteExpiredIsNoop()
    {
        $this->storage->recordFailure('bob');
        $this->storage->deleteExpired();
        // native TTL handles expiry, so the counter is untouched
        $this->assertSame(1, $this->storage->getFailures('bob')['count']);
    }
}

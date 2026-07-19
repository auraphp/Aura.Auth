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
}

<?php

namespace weanteomnio\AOCaptcha\Tests\Storage;

use PHPUnit\Framework\TestCase;
use weanteomnio\AOCaptcha\Storage\RedisStorage;
use weanteomnio\AOCaptcha\Tests\Fixtures\FakeRedisClient;

final class RedisStorageTest extends TestCase
{
    public function testSetThenGetRoundTripsNestedArray(): void
    {
        $storage = new RedisStorage(new FakeRedisClient());
        $value = ['target' => ['x' => 61, 'y' => 40], 'shape' => 'circle'];

        $storage->set('challenge:abc', $value, 300);

        $this->assertSame($value, $storage->get('challenge:abc'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $storage = new RedisStorage(new FakeRedisClient());

        $this->assertNull($storage->get('missing'));
    }

    public function testDeleteRemovesKey(): void
    {
        $client = new FakeRedisClient();
        $storage = new RedisStorage($client);
        $storage->set('k', ['v' => 1], 60);

        $storage->delete('k');

        $this->assertNull($storage->get('k'));
    }
}

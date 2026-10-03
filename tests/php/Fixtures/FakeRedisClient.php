<?php

namespace weanteomnio\AOCaptcha\Tests\Fixtures;

final class FakeRedisClient
{
    /** @var array<string, string> */
    private array $store = [];

    public function get(string $key): string|false
    {
        return $this->store[$key] ?? false;
    }

    public function setex(string $key, int $ttl, string $value): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function del(string $key): int
    {
        $existed = isset($this->store[$key]) ? 1 : 0;
        unset($this->store[$key]);
        return $existed;
    }
}

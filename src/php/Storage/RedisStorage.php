<?php

namespace weanteomnio\AOCaptcha\Storage;

/**
 * Stores challenge/pass state in Redis instead of PHP sessions, for
 * stateless or multi-server deployments.
 *
 * @see https://www.php.net/manual/en/book.redis.php
 */
final class RedisStorage implements StorageInterface
{
    /**
     * @param object $redis Any client exposing get(string):string|false,
     *                       setex(string,int,string):bool, del(string):int —
     *                       satisfied by \Redis from ext-redis.
     */
    public function __construct(private object $redis)
    {
    }

    public function get(string $key): ?array
    {
        $raw = $this->redis->get($key);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function set(string $key, array $value, int $ttlSeconds): void
    {
        $this->redis->setex($key, $ttlSeconds, json_encode($value));
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}

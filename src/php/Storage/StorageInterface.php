<?php

namespace weanteomnio\AOCaptcha\Storage;

interface StorageInterface
{
    /**
     * @return array<mixed>|null The stored value, or null if missing/expired.
     */
    public function get(string $key): ?array;

    /**
     * @param array<mixed> $value
     */
    public function set(string $key, array $value, int $ttlSeconds): void;

    public function delete(string $key): void;
}

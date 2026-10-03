<?php

namespace weanteomnio\AOCaptcha\Tests\Fixtures;

use weanteomnio\AOCaptcha\Storage\StorageInterface;

final class InMemoryStorage implements StorageInterface
{
    /** @var array<string, array{value: array<mixed>, exp: int}> */
    private array $data = [];

    public function get(string $key): ?array
    {
        $entry = $this->data[$key] ?? null;
        if ($entry === null) {
            return null;
        }
        if (time() > $entry['exp']) {
            unset($this->data[$key]);
            return null;
        }
        return $entry['value'];
    }

    public function set(string $key, array $value, int $ttlSeconds): void
    {
        $this->data[$key] = ['value' => $value, 'exp' => time() + $ttlSeconds];
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }
}

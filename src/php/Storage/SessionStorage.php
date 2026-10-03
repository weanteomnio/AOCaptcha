<?php

namespace weanteomnio\AOCaptcha\Storage;

final class SessionStorage implements StorageInterface
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function get(string $key): ?array
    {
        $entry = $_SESSION[$key] ?? null;
        if (!is_array($entry) || !array_key_exists('value', $entry) || !array_key_exists('exp', $entry)) {
            return null;
        }

        if (time() > (int) $entry['exp']) {
            unset($_SESSION[$key]);
            return null;
        }

        return $entry['value'];
    }

    public function set(string $key, array $value, int $ttlSeconds): void
    {
        $_SESSION[$key] = ['value' => $value, 'exp' => time() + $ttlSeconds];
    }

    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }
}

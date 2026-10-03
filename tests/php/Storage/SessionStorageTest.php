<?php

namespace weanteomnio\AOCaptcha\Tests\Storage;

use PHPUnit\Framework\TestCase;
use weanteomnio\AOCaptcha\Storage\SessionStorage;

final class SessionStorageTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testSetThenGetReturnsSameValue(): void
    {
        $storage = new SessionStorage();
        $storage->set('k1', ['a' => 1, 'b' => ['c' => 2]], 60);

        $this->assertSame(['a' => 1, 'b' => ['c' => 2]], $storage->get('k1'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $storage = new SessionStorage();

        $this->assertNull($storage->get('nope'));
    }

    public function testGetReturnsNullAfterExpiry(): void
    {
        $storage = new SessionStorage();
        $storage->set('k2', ['v' => 1], -1);

        $this->assertNull($storage->get('k2'));
    }

    public function testDeleteRemovesKey(): void
    {
        $storage = new SessionStorage();
        $storage->set('k3', ['v' => 1], 60);
        $storage->delete('k3');

        $this->assertNull($storage->get('k3'));
    }
}

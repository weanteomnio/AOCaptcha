<?php

namespace weanteomnio\AOCaptcha\Tests\Http;

use PHPUnit\Framework\TestCase;
use weanteomnio\AOCaptcha\BehaviorAnalyzer;
use weanteomnio\AOCaptcha\Challenge;
use weanteomnio\AOCaptcha\Http\CaptchaHandler;
use weanteomnio\AOCaptcha\Tests\Fixtures\InMemoryStorage;

final class CaptchaHandlerTest extends TestCase
{
    private function fixedChallenge(): Challenge
    {
        return new class extends Challenge {
            public function generate(): array
            {
                return [
                    'id' => 'fixed-id',
                    'shape' => 'circle',
                    'target' => ['x' => 65, 'y' => 50],
                    'start' => ['x' => 15, 'y' => 20],
                    'rotation' => 18,
                    'size' => 52,
                    'tolerance' => 22,
                    'prompt' => 'Align the shape to continue',
                ];
            }
        };
    }

    /** @return array<int, array{x:float,y:float,t:int}> */
    private function humanLikeMovements(): array
    {
        return [
            ['x' => 10.0, 'y' => 20.0, 't' => 0],
            ['x' => 14.0, 'y' => 19.0, 't' => 40],
            ['x' => 19.0, 'y' => 23.0, 't' => 130],
            ['x' => 23.0, 'y' => 30.0, 't' => 180],
            ['x' => 30.0, 'y' => 28.0, 't' => 310],
            ['x' => 34.0, 'y' => 35.0, 't' => 340],
            ['x' => 38.0, 'y' => 44.0, 't' => 520],
            ['x' => 46.0, 'y' => 41.0, 't' => 560],
            ['x' => 52.0, 'y' => 50.0, 't' => 810],
            ['x' => 60.0, 'y' => 58.0, 't' => 850],
            ['x' => 65.0, 'y' => 55.0, 't' => 1000],
            ['x' => 70.0, 'y' => 62.0, 't' => 1150],
        ];
    }

    public function testHandleNewReturnsChallengeAndStoresIt(): void
    {
        $storage = new InMemoryStorage();
        $handler = new CaptchaHandler($storage, $this->fixedChallenge(), new BehaviorAnalyzer());

        $result = $handler->handle('new', null, 'browser-1');

        $this->assertSame('circle', $result['shape']);
        $this->assertSame(['x' => 65, 'y' => 50], $result['target']);
    }

    public function testVerifyWithoutPriorNewFails(): void
    {
        $storage = new InMemoryStorage();
        $handler = new CaptchaHandler($storage, $this->fixedChallenge(), new BehaviorAnalyzer());

        $result = $handler->handle('verify', [
            'movements' => $this->humanLikeMovements(),
            'duration' => 1150,
            'snapped' => true,
            'snapPosition' => ['x' => 65, 'y' => 50],
        ], 'browser-1');

        $this->assertFalse($result['valid']);
    }

    public function testFullNewThenVerifyFlowSucceedsAndIssuesOneTimeToken(): void
    {
        $storage = new InMemoryStorage();
        $handler = new CaptchaHandler($storage, $this->fixedChallenge(), new BehaviorAnalyzer());

        $handler->handle('new', null, 'browser-1');

        $verifyResult = $handler->handle('verify', [
            'movements' => $this->humanLikeMovements(),
            'duration' => 1150,
            'snapped' => true,
            'snapPosition' => ['x' => 65, 'y' => 50],
        ], 'browser-1');

        $this->assertTrue($verifyResult['valid']);
        $this->assertIsString($verifyResult['token']);

        $this->assertTrue($handler->verifyPass($verifyResult['token'], 'browser-1'));
        $this->assertFalse(
            $handler->verifyPass($verifyResult['token'], 'browser-1'),
            'A pass token must be single-use.'
        );
    }

    public function testVerifyRejectsSnapPositionFarFromTarget(): void
    {
        $storage = new InMemoryStorage();
        $handler = new CaptchaHandler($storage, $this->fixedChallenge(), new BehaviorAnalyzer());

        $handler->handle('new', null, 'browser-1');

        $result = $handler->handle('verify', [
            'movements' => $this->humanLikeMovements(),
            'duration' => 1150,
            'snapped' => true,
            'snapPosition' => ['x' => 5, 'y' => 5],
        ], 'browser-1');

        $this->assertFalse($result['valid']);
    }

    public function testVerifyWithEmptyMovementsFailsWithoutError(): void
    {
        $storage = new InMemoryStorage();
        $handler = new CaptchaHandler($storage, $this->fixedChallenge(), new BehaviorAnalyzer());

        $handler->handle('new', null, 'browser-1');

        $result = $handler->handle('verify', [
            'movements' => [],
            'snapped' => true,
            'snapPosition' => ['x' => 65, 'y' => 50],
        ], 'browser-1');

        $this->assertFalse($result['valid']);
    }

    public function testUnknownActionReturnsMessage(): void
    {
        $storage = new InMemoryStorage();
        $handler = new CaptchaHandler($storage, $this->fixedChallenge(), new BehaviorAnalyzer());

        $result = $handler->handle('bogus', null, 'browser-1');

        $this->assertFalse($result['valid']);
        $this->assertSame('Unknown action.', $result['message']);
    }
}

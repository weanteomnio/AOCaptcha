<?php

namespace weanteomnio\AOCaptcha\Tests;

use PHPUnit\Framework\TestCase;
use weanteomnio\AOCaptcha\BehaviorAnalyzer;

final class BehaviorAnalyzerTest extends TestCase
{
    public function testTooFewMovementsIsRejected(): void
    {
        $analyzer = new BehaviorAnalyzer();

        $result = $analyzer->looksHuman(
            [['x' => 10, 'y' => 10, 't' => 0], ['x' => 20, 'y' => 20, 't' => 100]],
            500
        );

        $this->assertFalse($result);
    }

    public function testTooShortDurationIsRejected(): void
    {
        $analyzer = new BehaviorAnalyzer();
        $movements = [];
        for ($i = 0; $i < 8; $i++) {
            $movements[] = ['x' => $i * 5, 'y' => $i * 5, 't' => $i * 5];
        }

        $result = $analyzer->looksHuman($movements, 40);

        $this->assertFalse($result);
    }

    public function testTooLongDurationIsRejected(): void
    {
        $analyzer = new BehaviorAnalyzer();
        $movements = [];
        for ($i = 0; $i < 8; $i++) {
            $movements[] = ['x' => $i * 5, 'y' => $i * 5, 't' => $i * 1000];
        }

        $result = $analyzer->looksHuman($movements, 35000);

        $this->assertFalse($result);
    }

    public function testPerfectlyStraightLinePathIsRejectedAsBotLike(): void
    {
        $analyzer = new BehaviorAnalyzer();
        $movements = [];
        for ($i = 0; $i <= 10; $i++) {
            $movements[] = ['x' => 10 + $i * 6, 'y' => 10 + $i * 6, 't' => $i * 200];
        }

        $result = $analyzer->looksHuman($movements, 2000);

        $this->assertFalse($result, 'A perfectly straight, constant-velocity path must be rejected.');
    }

    public function testVariedCurvedPathIsAcceptedAsHumanLike(): void
    {
        $analyzer = new BehaviorAnalyzer();

        $movements = [
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

        $result = $analyzer->looksHuman($movements, 1150);

        $this->assertTrue($result, 'A varied-speed, zigzagging path must be accepted as human-like.');
    }
}

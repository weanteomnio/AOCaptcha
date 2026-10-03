<?php

namespace weanteomnio\AOCaptcha\Tests;

use PHPUnit\Framework\TestCase;
use weanteomnio\AOCaptcha\Challenge;

final class ChallengeTest extends TestCase
{
    public function testGenerateReturnsWellFormedChallenge(): void
    {
        $challenge = new Challenge();

        for ($i = 0; $i < 25; $i++) {
            $data = $challenge->generate();

            $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $data['id']);
            $this->assertContains($data['shape'], Challenge::defaultShapeNames());
            $this->assertGreaterThanOrEqual(58, $data['target']['x']);
            $this->assertLessThanOrEqual(78, $data['target']['x']);
            $this->assertGreaterThanOrEqual(30, $data['target']['y']);
            $this->assertLessThanOrEqual(70, $data['target']['y']);
            $this->assertGreaterThanOrEqual(8, $data['start']['x']);
            $this->assertLessThanOrEqual(28, $data['start']['x']);
            $this->assertGreaterThanOrEqual(15, $data['start']['y']);
            $this->assertLessThanOrEqual(85, $data['start']['y']);
            $this->assertTrue(
                ($data['rotation'] >= 8 && $data['rotation'] <= 28) ||
                ($data['rotation'] >= -28 && $data['rotation'] <= -8)
            );
            $this->assertGreaterThanOrEqual(40, $data['size']);
            $this->assertLessThanOrEqual(70, $data['size']);
            $this->assertSame(22, $data['tolerance']);
            $this->assertIsString($data['prompt']);
        }
    }

    public function testCustomShapesAndPromptsAreHonored(): void
    {
        $challenge = new Challenge(['circle'], ['Only prompt'], 10);

        $data = $challenge->generate();

        $this->assertSame('circle', $data['shape']);
        $this->assertSame('Only prompt', $data['prompt']);
        $this->assertSame(10, $data['tolerance']);
    }

    public function testDefaultShapesAreLoadedFromSharedConfigFile(): void
    {
        $configPath = dirname(__DIR__, 2) . '/aocaptcha.config.json';
        $config = json_decode((string) file_get_contents($configPath), true);

        $this->assertIsArray($config['shapes'] ?? null);
        $this->assertSame(array_keys($config['shapes']), Challenge::defaultShapeNames());
    }
}

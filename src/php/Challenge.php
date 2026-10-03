<?php

namespace weanteomnio\AOCaptcha;

class Challenge
{
    private const CONFIG_PATH = __DIR__ . '/../../aocaptcha.config.json';

    public const DEFAULT_PROMPTS = [
        'Align the shape to continue',
        'Drag into position',
        'Place the shape to proceed',
        'Complete the alignment',
    ];

    /**
     * @param string[]|null $shapes Defaults to every shape name in shapes.json.
     * @param string[] $prompts
     */
    public function __construct(
        private ?array $shapes = null,
        private array $prompts = self::DEFAULT_PROMPTS,
        private int $tolerance = 22,
    ) {
        $this->shapes = $shapes ?? self::defaultShapeNames();
    }

    /**
     * Shape names available in the shared shapes.json registry — the same
     * file the JS build reads to generate the widget's SVG shape map.
     *
     * @return string[]
     */
    public static function defaultShapeNames(): array
    {
        $config = json_decode((string) file_get_contents(self::CONFIG_PATH), true);

        return array_keys(is_array($config['shapes'] ?? null) ? $config['shapes'] : []);
    }

    /**
     * @return array{id:string,shape:string,target:array{x:int,y:int},start:array{x:int,y:int},rotation:int,size:int,tolerance:int,prompt:string}
     */
    public function generate(): array
    {
        $shape = $this->shapes[array_rand($this->shapes)];
        $targetX = random_int(58, 78);
        $targetY = random_int(30, 70);
        $startX = random_int(8, 28);
        $startY = random_int(15, 85);
        $rotation = random_int(0, 1) ? random_int(8, 28) : random_int(-28, -8);
        $size = random_int(40, 70);

        return [
            'id' => bin2hex(random_bytes(8)),
            'shape' => $shape,
            'target' => ['x' => $targetX, 'y' => $targetY],
            'start' => ['x' => $startX, 'y' => $startY],
            'rotation' => $rotation,
            'size' => $size,
            'tolerance' => $this->tolerance,
            'prompt' => $this->prompts[array_rand($this->prompts)],
        ];
    }
}

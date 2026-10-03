<?php

namespace weanteomnio\AOCaptcha;

class Challenge
{
    public const DEFAULT_SHAPES = [
        'rounded-square', 'circle', 'hexagon', 'diamond',
        'triangle', 'pentagon', 'octagon', 'star', 'cross',
        'heart', 'bolt', 'moon', 'arrow', 'shield', 'logo-triangle',
    ];

    public const DEFAULT_PROMPTS = [
        'Align the shape to continue',
        'Drag into position',
        'Place the shape to proceed',
        'Complete the alignment',
    ];

    /**
     * @param string[] $shapes
     * @param string[] $prompts
     */
    public function __construct(
        private array $shapes = self::DEFAULT_SHAPES,
        private array $prompts = self::DEFAULT_PROMPTS,
        private int $tolerance = 22,
    ) {
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

<?php

declare(strict_types=1);

namespace OlegV\Tests\Components\Button;

use OlegV\Brick;
use OlegV\Traits\WithStrictHelpers;

readonly class Button extends Brick
{
    //чисто для анализатора phpstan
    use WithStrictHelpers;

    public function __construct(
        public string $text = 'Click me',
        public string $variant = 'primary',
        public bool $disabled = false,
    ) {
        parent::__construct();
    }
}
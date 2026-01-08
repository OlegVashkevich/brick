<?php

declare(strict_types=1);

namespace OlegV\Tests\Components\Card;

use OlegV\Brick;

readonly class Card extends Brick
{
    public function __construct(
        public string $title = '',
        public string $content = '',
        public string $footer = '',
    ) {}
}
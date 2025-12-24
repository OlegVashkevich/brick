<?php
namespace OlegV\Tests\Components;

use OlegV\Brick;

class Card extends Brick
{
    public function __construct(
        public string $title = '',
        public string $content = '',
        public string $footer = ''
    ) {
        parent::__construct();
    }
}
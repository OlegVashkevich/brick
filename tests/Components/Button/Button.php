<?php
namespace OlegV\Tests\Components;

use OlegV\Brick;
use OlegV\WithInheritance;

class Button extends Brick
{
    public function __construct(
        public string $text = 'Click me',
        public string $variant = 'primary',
        public bool $disabled = false
    ) {
        parent::__construct();
    }
}
<?php
namespace OlegV\Tests\Components;
use OlegV\Brick;
use OlegV\Traits\WithCache;

readonly class CachedButton extends Brick {
    use WithCache;
    public function __construct(public string $text = "Click me", public string $variant = "primary", public bool $disabled = false) {
        parent::__construct();
    }
}
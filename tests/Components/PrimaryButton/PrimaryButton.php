<?php
namespace OlegV\Tests\Components;

use OlegV\WithInheritance;

class PrimaryButton extends Button
{
    use WithInheritance;
    public function __construct(
        string $text = 'Primary Button',
        bool $disabled = false
    ) {
        parent::__construct($text, 'primary', $disabled);
    }
}
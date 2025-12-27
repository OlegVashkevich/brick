<?php

declare(strict_types=1);

namespace OlegV\Tests\Components\PrimaryButton;

use OlegV\Tests\Components\Button\Button;
use OlegV\Traits\WithInheritance;

readonly class PrimaryButton extends Button
{
    use WithInheritance;

    public function __construct(
        string $text = 'Primary Button',
        bool $disabled = false,
    ) {
        parent::__construct($text, 'primary', $disabled);
    }
}
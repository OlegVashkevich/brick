<?php
namespace OlegV\Tests\Components;

use OlegV\Brick;

readonly class InvalidComponent extends Brick {
    public function __construct() {
        parent::__construct();
    }
}
<?php

declare(strict_types=1);

/** @var Card $this */

use OlegV\Tests\Components\Card\Card;

?>
<div class="card">
    <?php
    if ($this->title !== ''): ?>
        <div class="card-header">
            <h3><?= $this->e($this->title) ?></h3>
        </div>
    <?php
    endif; ?>

    <div class="card-body">
        <?= $this->content ?>
    </div>

    <?php
    if ($this->footer !== ''): ?>
        <div class="card-footer"><?= $this->e($this->footer) ?></div>
    <?php
    endif; ?>
</div>
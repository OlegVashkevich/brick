<?php
/** @var Button $this */

use OlegV\Tests\Components\Button;
?>
<button
    class="btn btn-<?= $this->variant ?><?= $this->disabled ? ' disabled' : '' ?>"
    <?= $this->disabled ? 'disabled' : '' ?>
><?= $this->e($this->text) ?></button>
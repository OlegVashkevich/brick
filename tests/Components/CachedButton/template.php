<?php

declare(strict_types=1);

/** @var CachedButton $this */

use OlegV\Tests\Components\CachedButton\CachedButton;

?>
<button class="btn btn-<?= $this->variant ?>"><?= $this->e($this->text) ?></button>
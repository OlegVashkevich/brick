<?php

declare(strict_types=1);

namespace OlegV\Assets;

interface AssetRenderer
{
    /**
     * @param  array<string, string>  $cssAssets
     * @return string
     */
    public function renderCss(array $cssAssets): string;

    /**
     * @param  array<string, string>  $jsAssets
     * @return string
     */
    public function renderJs(array $jsAssets): string;
}
<?php

declare(strict_types=1);

namespace OlegV\Assets;

class InlineAssetRenderer implements AssetRenderer
{
    /**
     * @param  array<string, string>  $cssAssets
     * @return string
     */
    public function renderCss(array $cssAssets): string
    {
        if ($cssAssets===[]) {
            return '';
        }

        $css = implode("\n\n", $cssAssets);
        return "<style>\n$css\n</style>";
    }

    /**
     * @param  array<string, string>  $jsAssets
     * @return string
     */
    public function renderJs(array $jsAssets): string
    {
        if ($jsAssets===[]) {
            return '';
        }

        $js = implode("\n\n", $jsAssets);
        return "<script>\n$js\n</script>";
    }
}
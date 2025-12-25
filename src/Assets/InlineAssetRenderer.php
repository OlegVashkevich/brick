<?php

declare(strict_types=1);

namespace OlegV\Assets;

/**
 * @example
 * $inlineRenderer = new InlineAssetRenderer();
 * $inlineRenderer->setMinify(true);
 * $inlineRenderer->setMode(InlineAssetRenderer::MODE_MULTIPLE);
 *  TODO: тесты и phpstan
 */
class InlineAssetRenderer extends AbstractAssetRenderer
{
    public function renderCss(array $cssAssets): string
    {
        if ($cssAssets === []) {
            return '';
        }

        $processed = $this->processCssAssets($cssAssets);
        $styles = [];

        foreach ($processed as $css) {
            $styles[] = "<style>\n$css\n</style>";
        }

        return implode("\n", $styles);
    }

    public function renderJs(array $jsAssets): string
    {
        if ($jsAssets === []) {
            return '';
        }

        $processed = $this->processJsAssets($jsAssets);
        $scripts = [];

        foreach ($processed as $js) {
            $scripts[] = "<script>\n$js\n</script>";
        }

        return implode("\n", $scripts);
    }
}
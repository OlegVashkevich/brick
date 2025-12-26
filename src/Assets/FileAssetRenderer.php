<?php

declare(strict_types=1);

namespace OlegV\Assets;


/**
 * @example
 * // 1. FileAssetRenderer
 * $fileRenderer = new FileAssetRenderer(
 * __DIR__ . '/public/assets',
 * '/assets/',
 * true,  // минификация
 * FileAssetRenderer::MODE_SINGLE
 * );
 * $fileRenderer->setMinify(true);
 * $fileRenderer->setMode(InlineAssetRenderer::MODE_MULTIPLE);
 */
class FileAssetRenderer extends AbstractAssetRenderer
{
    private string $outputDir;
    private string $publicUrl;
    private string $filePrefix;

    public function __construct(
        string $outputDir,
        string $publicUrl = '/assets/',
        bool $minify = false,
        string $mode = self::MODE_SINGLE,
        string $filePrefix = 'brick'
    ) {
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->publicUrl = rtrim($publicUrl, '/') . '/';
        $this->filePrefix = $filePrefix;
        $this->minify = $minify;
        $this->mode = $mode;

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function renderCss(array $cssAssets): string
    {
        if ($cssAssets === []) {
            return '';
        }

        $processed = $this->processCssAssets($cssAssets);
        $links = [];

        foreach ($processed as $id => $css) {
            $hash = md5($css);
            $filename = $this->generateFilename($id, $hash, 'css');
            $filepath = $this->outputDir . $filename;

            $this->writeFileIfNotExists($filepath, $css);

            $links[] = '<link rel="stylesheet" href="' . $this->publicUrl . $filename . '">';
        }

        return implode("\n", $links);
    }

    public function renderJs(array $jsAssets): string
    {
        if ($jsAssets === []) {
            return '';
        }

        $processed = $this->processJsAssets($jsAssets);
        $scripts = [];

        foreach ($processed as $id => $js) {
            $hash = md5($js);
            $filename = $this->generateFilename($id, $hash, 'js');
            $filepath = $this->outputDir . $filename;

            $this->writeFileIfNotExists($filepath, $js);

            $scripts[] = '<script src="' . $this->publicUrl . $filename . '"></script>';
        }

        return implode("\n", $scripts);
    }

    /**
     * Генерация имени файла
     */
    private function generateFilename(string $id, string $hash, string $extension): string
    {
        $mode = $this->mode === self::MODE_SINGLE ? 'all' : $id;
        return sprintf(
            '%s.%s.%s.%s',
            $this->filePrefix,
            $mode,
            substr($hash, 0, 8),
            $extension
        );
    }

    /**
     * Записать файл, если он не существует
     */
    private function writeFileIfNotExists(string $filepath, string $content): void
    {
        if (!file_exists($filepath)) {
            file_put_contents($filepath, $content);
        }
    }
}
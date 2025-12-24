<?php

declare(strict_types=1);

namespace OlegV\Assets;

class FileAssetRenderer implements AssetRenderer
{
    private string $outputDir;
    private string $publicUrl;
    private bool $minify;

    public function __construct(
        string $outputDir,
        string $publicUrl = '/assets/',
        bool $minify = false
    ) {
        $this->outputDir = rtrim($outputDir, '/') . '/';
        $this->publicUrl = rtrim($publicUrl, '/') . '/';
        $this->minify = $minify;

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function renderCss(array $cssAssets): string
    {
        if ($cssAssets===[]) {
            return '';
        }

        $css = implode("\n\n", $cssAssets);

        if ($this->minify) {
            $css = $this->minifyCss($css);
        }

        $filename = 'brick.' . md5($css) . '.css';
        $filepath = $this->outputDir . $filename;

        if (!file_exists($filepath)) {
            file_put_contents($filepath, $css);
        }

        return '<link rel="stylesheet" href="' . $this->publicUrl . $filename . '">';
    }

    public function renderJs(array $jsAssets): string
    {
        if ($jsAssets === []) {
            return '';
        }

        $js = implode("\n\n", $jsAssets);

        if ($this->minify) {
            $js = $this->minifyJs($js);
        }

        $filename = 'brick.' . md5($js) . '.js';
        $filepath = $this->outputDir . $filename;

        if (!file_exists($filepath)) {
            file_put_contents($filepath, $js);
        }

        return '<script src="' . $this->publicUrl . $filename . '"></script>';
    }

    private function minifyCss(string $css): string
    {
        // Удаляем комментарии
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css) ?? '';

        // Удаляем пробелы, переносы строк, табы
        $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
        $css = preg_replace('/\s+/', ' ', $css) ?? '';

        // Удаляем лишние пробелы вокруг символов
        $css = preg_replace('/\s*([{}|:;,])\s*/', '$1', $css) ?? '';
        $css = preg_replace('/;}/', '}', $css) ?? '';

        return trim($css);
    }

    private function minifyJs(string $js): string
    {
        // Удаляем однострочные комментарии
        $js = preg_replace('/(?:^|\s)\/\/.*$/m', '', $js) ?? '';

        // Удаляем многострочные комментарии
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js) ?? '';

        // Удаляем лишние пробелы и переносы строк
        $js = preg_replace('/\s+/', ' ', $js) ?? '';

        return trim($js);
    }
}
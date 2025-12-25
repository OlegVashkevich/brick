<?php

declare(strict_types=1);

namespace OlegV\Assets;

use InvalidArgumentException;

/**
 *  TODO: тесты и phpstan
 */
abstract class AbstractAssetRenderer implements AssetRenderer
{
    protected const MODE_SINGLE = 'single';
    protected const MODE_MULTIPLE = 'multiple';

    protected bool $minify = false;
    protected string $mode = self::MODE_SINGLE;

    /**
     * Минифицировать CSS
     */
    protected function minifyCss(string $css): string
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

    /**
     * Минифицировать JavaScript
     */
    protected function minifyJs(string $js): string
    {
        // Удаляем однострочные комментарии
        $js = preg_replace('/(?:^|\s)\/\/.*$/m', '', $js) ?? '';

        // Удаляем многострочные комментарии
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js) ?? '';

        // Удаляем лишние пробелы и переносы строк
        $js = preg_replace('/\s+/', ' ', $js) ?? '';

        return trim($js);
    }

    /**
     * Объединить CSS ассеты согласно режиму
     *
     * @param  array<string, string>  $cssAssets
     * @return array<string, string> Группированные ассеты
     */
    protected function processCssAssets(array $cssAssets): array
    {
        if ($this->mode === self::MODE_SINGLE) {
            $css = implode("\n\n", $cssAssets);
            if ($this->minify) {
                $css = $this->minifyCss($css);
            }
            return ['all' => $css];
        } else {
            $result = [];
            foreach ($cssAssets as $componentName => $css) {
                if ($css === '') {
                    continue;
                }
                $id = $this->getComponentId($componentName);
                $processed = $css;
                if ($this->minify) {
                    $processed = $this->minifyCss($processed);
                }
                $result[$id] = $processed;
            }
            return $result;
        }
    }

    /**
     * Объединить JS ассеты согласно режиму
     *
     * @param  array<string, string>  $jsAssets
     * @return array<string, string> Группированные ассеты
     */
    protected function processJsAssets(array $jsAssets): array
    {
        if ($this->mode === self::MODE_SINGLE) {
            $js = implode("\n\n", $jsAssets);
            if ($this->minify) {
                $js = $this->minifyJs($js);
            }
            return ['all' => $js];
        } else {
            $result = [];
            foreach ($jsAssets as $componentName => $js) {
                if ($js === '') {
                    continue;
                }
                $id = $this->getComponentId($componentName);
                $processed = $js;
                if ($this->minify) {
                    $processed = $this->minifyJs($processed);
                }
                $result[$id] = $processed;
            }
            return $result;
        }
    }

    /**
     * Получить идентификатор компонента из полного имени класса
     */
    protected function getComponentId(string $className): string
    {
        $parts = explode('\\', $className);
        $lastPart = end($parts);

        $id = preg_replace('/(?<!^)[A-Z]/', '-$0', $lastPart);
        return strtolower($id);
    }

    /**
     * Установить режим объединения
     */
    public function setMode(string $mode): void
    {
        if (!in_array($mode, [self::MODE_SINGLE, self::MODE_MULTIPLE])) {
            throw new InvalidArgumentException("Неизвестный режим: $mode");
        }
        $this->mode = $mode;
    }

    /**
     * Включить/выключить минификацию
     */
    public function setMinify(bool $minify): void
    {
        $this->minify = $minify;
    }

    /**
     * Получить текущий режим
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Получена ли минификация
     */
    public function isMinify(): bool
    {
        return $this->minify;
    }

    /**
     * Получить список доступных режимов
     */
    public static function getAvailableModes(): array
    {
        return [
            self::MODE_SINGLE => 'Все компоненты в один файл',
            self::MODE_MULTIPLE => 'Каждый компонент в отдельный файл'
        ];
    }
}
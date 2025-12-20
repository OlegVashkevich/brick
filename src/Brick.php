<?php

/**
 * 🧱 Brick - Базовый PHP класс для UI-компонентов
 * Механизм для создания типизированных, композитных UI-компонентов.
 * Создавайте свои компоненты, наследуясь от Brick.
 *
 * @package Brick
 * @version 0.0.1
 * @license MIT
 */

declare(strict_types=1);

namespace OlegV;

use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Базовый класс для всех компонентов Brick
 *
 * Каждый компонент состоит из 4 файлов в одной папке:
 * 1. ИмяКласса.php - PHP класс с промоутед-свойствами
 * 2. template.php   - HTML шаблон с PHP кодом
 * 3. style.css      - Стили компонента (опционально)
 * 4. script.js      - JavaScript компонента (опционально)
 *
 * @example
 * // Button/Button.php
 * class Button extends Brick {
 *     public function __construct(
 *         public string $text,
 *         public string $variant = 'primary'
 *     ) {
 *         parent::__construct(); // Автоматически находит файлы в папке Button/
 *     }
 * }
 */
abstract class Brick
{
    /**
     * Директория компонента (где лежат template.php, style.css, script.js)
     */
    protected string $dir;

    /**
     * Статический реестр ассетов всех компонентов
     */
    private static array $cssAssets = [];
    private static array $jsAssets = [];

    /**
     * Кэш всех данных компонента (статический, на уровне класса)
     */
    private static array $classCache = [];

    /**
     * Конструктор компонента
     *
     * Автоматически находит файлы компонента в той же директории
     *
     * @throws RuntimeException если template.php не найден
     */
    public function __construct()
    {
        $className = static::class;

        // ВСЁ за один проход - все операции только один раз на класс!
        if (!isset(self::$classCache[$className])) {
            $reflection = new ReflectionClass($className);
            $dir = dirname($reflection->getFileName());
            $templatePath = $dir . '/template.php';

            // Валидация шаблона
            if (!file_exists($templatePath)) {
                throw new RuntimeException(
                    "Компонент '{$reflection->getShortName()}' требует template.php в: $dir"
                );
            }

            // Загрузка ассетов
            $css = file_exists($dir . '/style.css')
                ? file_get_contents($dir . '/style.css')
                : null;
            $js = file_exists($dir . '/script.js')
                ? file_get_contents($dir . '/script.js')
                : null;

            // Сохраняем ВСЕ данные о классе
            self::$classCache[$className] = [
                'dir' => $dir,
                'css' => $css,
                'js' => $js,
                'template_mtime' => filemtime($templatePath),
                'template_content' => null, // Ленивая загрузка при первом рендере
            ];

            // Регистрируем ассеты в статических реестрах
            if ($css !== null) {
                self::$cssAssets[$className] = $css;
            }
            if ($js !== null) {
                self::$jsAssets[$className] = $js;
            }
        }

        $this->dir = self::$classCache[$className]['dir'];
    }

    /**
     * Рендерит компонент в HTML
     */
    public function render(): string
    {
        ob_start();

        try {
            // $this доступен в шаблоне как $component
            $component = $this;

            include $this->dir . '/template.php';
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException(
                sprintf(
                    'Ошибка рендеринга компонента %s: %s',
                    static::class,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return (string) ob_get_clean();
    }

    /**
     * Преобразование в строку = рендеринг
     */
    public function __toString(): string
    {
        return $this->render();
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ДЛЯ ШАБЛОНОВ ====================

    /**
     * Экранирование HTML специальных символов
     *
     * @example <?= $this->e($title) ?>
     */
    protected function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Создание строки CSS классов из массива
     *
     * @example class="<?= $this->classList(['btn', 'btn-primary']) ?>"
     */
    protected function classList(array $classes): string
    {
        return implode(' ', array_filter($classes));
    }

    // ==================== СТАТИЧЕСКИЕ МЕТОДЫ ДЛЯ УПРАВЛЕНИЯ АССЕТАМИ ====================

    /**
     * Рендерит все зарегистрированные CSS стили
     */
    public static function renderCss(): string
    {
        if (empty(self::$cssAssets)) {
            return '';
        }

        $css = implode("\n\n", self::$cssAssets);
        return "<style>\n$css\n</style>";
    }

    /**
     * Рендерит весь зарегистрированный JavaScript
     */
    public static function renderJs(): string
    {
        if (empty(self::$jsAssets)) {
            return '';
        }

        $js = implode("\n\n", self::$jsAssets);
        return "<script>\n$js\n</script>";
    }

    /**
     * Рендерит все ассеты (CSS + JavaScript)
     */
    public static function renderAssets(): string
    {
        return self::renderCss() . "\n" . self::renderJs();
    }

    /**
     * Очищает реестр ассетов (полезно для тестирования)
     */
    public static function clear(): void
    {
        self::$cssAssets = [];
        self::$jsAssets = [];
        self::$classCache = [];
    }

    /**
     * Получить статистику кэша (для отладки)
     */
    public static function getCacheStats(): array
    {
        return [
            'cached_classes' => count(self::$classCache),
            'css_assets' => count(self::$cssAssets),
            'js_assets' => count(self::$jsAssets),
        ];
    }
}

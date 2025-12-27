<?php

declare(strict_types=1);

/**
 * 🧱 Brick - Базовый PHP класс для UI-компонентов
 * Механизм для создания типизированных, композитных UI-компонентов.
 * Создавайте свои компоненты, наследуясь от Brick.
 *
 * @package Brick
 * @version 0.0.1
 * @license MIT
 */

namespace OlegV;

use OlegV\Traits\WithInheritance;
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
abstract readonly class Brick
{
    /**
     * Конструктор компонента
     *
     * Автоматически находит файлы компонента в той же директории
     *
     * @throws RuntimeException если template.php не найден
     */
    public function __construct()
    {
        // Проверяем, что текущий класс не использует этот трейт
        $currentClassTraits = class_uses($this);

        $className = static::class;

        $manager = BrickManager::getInstance();

        if (!in_array(WithInheritance::class, $currentClassTraits, true)) {
            // Проверяем кэш
            if ($manager->isComponentCached($className)) {
                $this->useCachedData($className, $manager);
                return;
            }

            $reflection = new ReflectionClass($className);
            $dir = dirname((string)$reflection->getFileName());
            $templatePath = $dir.'/template.php';

            if (!file_exists($templatePath)) {
                throw new RuntimeException("template.php не найден");
            }

            $css = file_exists($dir.'/style.css')
                ? (string)file_get_contents($dir.'/style.css')
                : '';
            $js = file_exists($dir.'/script.js')
                ? (string)file_get_contents($dir.'/script.js')
                : '';

            // Кэшируем в менеджере
            $manager->cacheComponent(
                className: $className,
                dir: $dir,
                templatePath: $templatePath,
                css: $css,
                js: $js,
            );
        } else {
            //используем метод из trait WithInheritance
            $this->initializeComponent($manager);
        }
    }

    /**
     * Метод инициализации, который переопределяют компоненты
     * Заглушка для WithInheritance
     * @param  BrickManager  $manager
     */
    protected function initializeComponent(BrickManager $manager): void {}


    /**
     * @param  string  $className
     * @param  BrickManager  $manager
     * @return void
     */
    protected function useCachedData(string $className, BrickManager $manager): void
    {
        $cached = $manager->getCachedComponent($className);

        if ($cached === null) {
            throw new RuntimeException(
                sprintf('Кэшированные данные не найдены для %s', $className),
            );
        }
    }

    /**
     * Рендерит компонент в HTML
     * @return string
     */
    public function render(): string
    {
        ob_start();
        try {
            $className = static::class;
            $manager = BrickManager::getInstance();
            $cached = $manager->getCachedComponent($className);

            if (!isset($cached['templatePath'])) {
                throw new RuntimeException(
                    sprintf('Не найден путь к шаблону для компонента %s', $className),
                );
            }

            include $cached['templatePath'];
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException(
                sprintf(
                    'Ошибка рендеринга компонента %s: %s',
                    static::class,
                    $e->getMessage(),
                ),
                0,
                $e,
            );
        }

        return (string)ob_get_clean();
    }

    /**
     * Преобразование в строку = рендеринг
     * @return string
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
     * @param  string  $value
     * @return string
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

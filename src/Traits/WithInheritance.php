<?php

declare(strict_types=1);

namespace OlegV\Traits;

use OlegV\Brick;
use OlegV\BrickManager;
use OlegV\Clay;
use ReflectionClass;
use RuntimeException;

/**
 * Трейт для наследования шаблонов и ресурсов компонентов
 *
 * Позволяет создавать иерархию компонентов с наследованием:
 * - Шаблонов (template.php)
 * - Стилей (style.css)
 * - Скриптов (script.js)
 *
 * Как работает:
 * 1. Автоматически ищет файлы по цепочке наследования классов
 * 2. Шаблон: использует первый найденный
 * 3. CSS/JS: объединяет все файлы от родителя к потомку
 * 4. Регистрирует всё в BrickManager через memoizeComponent()
 *
 * Пример наследования:
 * - BaseCard/ содержит template.php, style.css, script.js
 * - ProductCard/ содержит только style.css, script.js
 * - Результат: шаблон BaseCard + объединённые CSS/JS
 *
 * @example
 * // Создание иерархии компонентов
 * class BaseCard extends Brick {
 *     // Базовый шаблон и стили
 * }
 *
 * class ProductCard extends BaseCard {
 *      use WithInheritance;
 *     // Наследует шаблон BaseCard, добавляет свои стили
 * }
 */
trait WithInheritance
{
    protected function initializeWithInheritanceComponent(BrickManager $manager): void
    {
        $className = static::class;

        // ОДИН проход по иерархии
        $data = $this->findTemplateAndAssets();

        // Регистрируем в менеджере
        $manager->memoizeComponent(
            className: $className,
            dir: $data['dir'],
            templatePath: $data['templatePath'],
            css: $data['css'],
            js: $data['js'],
        );
    }

    /**
     * Находит все файлы (шаблон, CSS, JS) по цепочке наследования компонента
     *
     * Алгоритм поиска:
     * 1. Поднимается по иерархии классов от текущего до Brick/Clay
     * 2. Шаблон (template.php): берёт первый найденный
     * 3. CSS/JS (style.css, script.js): собирает ВСЕ файлы по цепочке
     * 4. Порядок CSS/JS: от родителя к потомку (через array_reverse)
     *
     * Пример для иерархии: ProductCard → BaseCard → Brick
     * - Шаблон: BaseCard/template.php (если ProductCard не имеет своего)
     * - CSS: BaseCard/style.css + ProductCard/style.css
     * - JS: BaseCard/script.js + ProductCard/script.js
     *
     * @return array{
     *     dir: string,
     *     templatePath: string,
     *     css: string,
     *     js: string
     * }
     * @throws RuntimeException Если ни один шаблон не найден
     */
    private function findTemplateAndAssets(): array
    {
        $className = static::class;
        $reflection = new ReflectionClass($className);
        $currentClass = $reflection;

        $cssParts = [];
        $jsParts = [];
        $templatePath = '';

        // Проходим всю цепочку до Brick
        while ($currentClass) {
            $dir = dirname((string)$currentClass->getFileName());

            // Ищем template (первый найденный - от самого "старшего" родителя)
            if ($templatePath === '') {
                $possibleTemplate = $dir.'/template.php';
                if (file_exists($possibleTemplate)) {
                    $templatePath = $possibleTemplate;
                }
            }

            // CSS
            $cssPath = $dir.'/style.css';
            if (file_exists($cssPath)) {
                $cssContent = (string)file_get_contents($cssPath);
                if ($cssContent !== '') {
                    $cssParts[] = $cssContent;
                }
            }

            // JS
            $jsPath = $dir.'/script.js';
            if (file_exists($jsPath)) {
                $jsContent = (string)file_get_contents($jsPath);
                if ($jsContent !== '') {
                    $jsParts[] = $jsContent;
                }
            }

            // Переходим к родителю, останавливаемся на Brick или Clay
            $parent = $currentClass->getParentClass();
            if ($parent === false || $parent->name === Brick::class || $parent->name === Clay::class) {
                break;
            }

            $currentClass = $parent;
        }

        if ($templatePath === '') {
            throw new RuntimeException("Шаблон не найден в цепочке наследования");
        }

        // CSS/JS уже в порядке от потомка к родителю, нужно развернуть
        return [
            'dir' => dirname($templatePath),
            'templatePath' => $templatePath,
            'css' => implode("\n\n", array_reverse($cssParts)),
            'js' => implode("\n\n", array_reverse($jsParts)),
        ];
    }
}
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
    protected function initialize(BrickManager $manager): void
    {
        // Проверяем, явно ли используется WithInheritance в текущем классе
        // Без проверки class_uses($this) все потомки BaseCard получат WithInheritance,
        // даже если они не хотели этого.
        $currentClassTraits = class_uses($this);
        if (!in_array(WithInheritance::class, $currentClassTraits, true)) {
            $this->initializeComponent($manager);
        } else {
            // ОДИН проход по иерархии
            $this->findTemplateAndAssets();
        }
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
     * @throws RuntimeException Если ни один шаблон не найден
     */
    private function findTemplateAndAssets(): void
    {
        $className = static::class;
        $reflection = new ReflectionClass($className);
        $currentClass = $reflection;

        // 1. Собираем ВСЁ по цепочке
        $classData = [];

        while ($currentClass) {
            $classKey = $currentClass->getName();
            $dir = dirname((string)$currentClass->getFileName());

            // Инициализируем запись для класса
            $classData[$classKey] = [
                'dir' => $dir,
                'templatePath' => null,
                'css' => '',
                'js' => '',
            ];

            // template.php
            $possibleTemplate = $dir.'/template.php';
            if (file_exists($possibleTemplate)) {
                $classData[$classKey]['templatePath'] = $possibleTemplate;
            }

            // CSS
            $cssPath = $dir.'/style.css';
            if (file_exists($cssPath)) {
                $cssContent = (string)file_get_contents($cssPath);
                if ($cssContent !== '') {
                    $classData[$classKey]['css'] = $cssContent;
                }
            }

            // JS
            $jsPath = $dir.'/script.js';
            if (file_exists($jsPath)) {
                $jsContent = (string)file_get_contents($jsPath);
                if ($jsContent !== '') {
                    $classData[$classKey]['js'] = $jsContent;
                }
            }

            // Переходим к родителю
            $parent = $currentClass->getParentClass();
            if ($parent === false || $parent->name === Brick::class || $parent->name === Clay::class) {
                break;
            }

            $currentClass = $parent;
        }

        // 2. Переворачиваем массив - от родителя к потомку
        $classData = array_reverse($classData, true);

        // 3. Находим ближайший шаблон (первый не-null templatePath)
        $templatePath = null;
        foreach ($classData as $data) {
            if ($data['templatePath'] !== null) {
                $templatePath = $data['templatePath'];
                break; // Берем первый найденный (самого старшего предка)
            }
        }

        if ($templatePath === null) {
            throw new RuntimeException("Шаблон не найден в цепочке наследования");
        }

        // 4. Регистрируем ВСЕ классы в цепочке
        $manager = BrickManager::getInstance();

        foreach ($classData as $class => $data) {
            // Если у текущего класса нет своего шаблона - используем найденный
            $currentTemplatePath = $data['templatePath'] ?? $templatePath;

            //уже зарегистрированных пропускаем
            if ($manager->isComponentMemoized($class)) {
                continue;
            }

            // Регистрируем класс в менеджере
            $manager->memoizeComponent(
                className: $class,
                dir: $data['dir'],
                templatePath: $currentTemplatePath,
                css: $data['css'],
                js: $data['js'],
            );
        }
    }
}
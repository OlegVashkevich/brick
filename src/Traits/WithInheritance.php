<?php

declare(strict_types=1);

namespace OlegV\Traits;

use OlegV\Brick;
use ReflectionClass;
use RuntimeException;

trait WithInheritance
{
    protected function initializeComponent(): void
    {
        $className = static::class;

        // ОДИН проход по иерархии
        $data = $this->findTemplateAndAssets();

        // Сохраняем в кэш
        Brick::$classCache[$className] = [
            'dir' => $data['dir'],
            'templatePath' => $data['templatePath'],
            'css' => $data['css'],
            'js' => $data['js']
        ];

        $this->dir = $data['dir'];
        $this->templatePath = $data['templatePath'];

        if ($data['css'] !== '') Brick::$cssAssets[$className] = $data['css'];
        if ($data['js'] !== '') Brick::$jsAssets[$className] = $data['js'];
    }

    /**
     * Кэш всех данных компонента (статический, на уровне класса)
     *
     * @return array{dir: string, templatePath: string, css: string, js: string}
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
            $dir = dirname((string) $currentClass->getFileName());

            // Ищем template (первый найденный - от самого "старшего" родителя)
            if ($templatePath === '') {
                $possibleTemplate = $dir . '/template.php';
                if (file_exists($possibleTemplate)) {
                    $templatePath = $possibleTemplate;
                }
            }

            // CSS
            $cssPath = $dir . '/style.css';
            if (file_exists($cssPath)) {
                $cssContent = (string) file_get_contents($cssPath);
                if ($cssContent !== '') {
                    $cssParts[] = $cssContent;
                }
            }

            // JS
            $jsPath = $dir . '/script.js';
            if (file_exists($jsPath)) {
                $jsContent = (string) file_get_contents($jsPath);
                if ($jsContent !== '') {
                    $jsParts[] = $jsContent;
                }
            }

            // Переходим к родителю, останавливаемся на Brick
            $parent = $currentClass->getParentClass();
            if ($parent === false || $parent->name === Brick::class) {
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
            'js' => implode("\n\n", array_reverse($jsParts))
        ];
    }
}
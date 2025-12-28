<?php

declare(strict_types=1);

namespace OlegV;

use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Внутренний служебный трейт для реализации базовой логики компонентов.
 *
 * ⚠️ НЕ ИСПОЛЬЗУЙТЕ ЭТОТ ТРЕЙТ НАПРЯМУЮ В КОМПОНЕНТАХ!
 *
 * Этот трейт используется только базовыми классами Brick и Clay.
 * Для добавления функциональности в компоненты используйте другие трейты:
 * - WithCache - для кэширования
 * - WithHelpers - для вспомогательных методов
 * - WithStrictHelpers - для строгих проверок типов для шаблонов
 * - WithInheritance - для наследования шаблонов и асетов
 *
 * @internal
 */
trait Mold
{
    /**
     */
    public function __construct()
    {
        $manager = BrickManager::getInstance();
        $this->initialize($manager);
    }

    /**
     * Метод инициализации который может быть заменен трейтами.
     * По умолчанию вызывает стандартный метод initializeComponent
     * @param  BrickManager  $manager
     * @return void
     */
    protected function initialize(BrickManager $manager): void
    {
        $this->initializeComponent($manager);
    }

    /**
     * Автоматически находит и регистрирует файлы компонента
     *
     * Логика поиска:
     * 1. Ищет файлы в папке компонента
     * 2. Читает template.php (обязателен)
     * 3. Загружает style.css и script.js если существуют в BrickManager
     * 4. Регистрирует все данные в BrickManager
     * @param  BrickManager  $manager
     */
    protected function initializeComponent(BrickManager $manager): void
    {
        $className = static::class;
        // Проверяем регистр
        if ($manager->isComponentMemoized($className)) {
            $cached = $manager->getMemoizedComponent($className);

            if ($cached === null) {
                throw new RuntimeException(
                    sprintf('Кэшированные данные не найдены для %s', $className),
                );
            }
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

        // Регистрируем в менеджере
        $manager->memoizeComponent(
            className: $className,
            dir: $dir,
            templatePath: $templatePath,
            css: $css,
            js: $js,
        );
    }

    public function render(): string
    {
        return $this->renderOriginal();
    }

    /**
     * Рендерит компонент в HTML
     * @return string
     */
    public function renderOriginal(): string
    {
        ob_start();
        try {
            $className = static::class;
            $manager = BrickManager::getInstance();
            $cached = $manager->getMemoizedComponent($className);

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

    /**
     * Экранирование HTML специальных символов
     *
     * @example <?= $this->e($title) ?>
     * @param  ?string  $value
     * @return string
     */
    public function e(?string $value = null): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
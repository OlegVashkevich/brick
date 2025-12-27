<?php

declare(strict_types=1);

namespace OlegV;

use OlegV\Traits\WithInheritance;
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
 * - WithInheritance - для наследования шаблонов и асетов
 *
 * @internal
 */
trait Mold
{
    /**
     * Автоматически находит и кэширует файлы компонента
     *
     * Логика поиска:
     * 1. Ищет файлы в папке компонента
     * 2. Читает template.php (обязателен)
     * 3. Загружает style.css и script.js если существуют в BrickManager
     * 4. Кэширует все данные в BrickManager
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
<?php

declare(strict_types=1);

namespace OlegV;

use OlegV\Assets\AssetRenderer;
use Psr\SimpleCache\CacheInterface;

/**
 * Центральный менеджер для управления всеми компонентами Brick v2.0
 */
final class BrickManager
{
    /**
     * Единственный экземпляр
     */
    private static ?self $instance = null;

    /**
     * Кэш данных компонентов
     * @var array<string, array{dir: string, templatePath: string, css: string, js: string}>
     */
    private array $classCache = [];

    /**
     * PSR-16 кэш для рендера, если используется trait WithCache
     * @var CacheInterface|null
     */
    private static ?CacheInterface $cache = null;
    /**
     * Префикс для ключей кэша
     */
    public static string $cachePrefix = 'brick_';
    /**
     * Время жизни кэша по умолчанию (в секундах)
     */
    public static int $cacheTtl = 3600;

    public function __construct(
        private readonly AssetRenderer $assetRenderer = new Assets\InlineAssetRenderer(),
    ) {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Устанавливает PSR-16 кэш для всех компонентов
     */
    public static function setCache(CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * Получает настроенный кэш
     */
    public static function getCache(): ?CacheInterface
    {
        return self::$cache;
    }

    // ==================== Регистрация компонентов ====================

    /**
     * Регистрирует компонент в кэше менеджера
     * @param  string  $className  Полное имя класса компонента
     * @param  string  $dir  Директория компонента
     * @param  string  $templatePath  Путь к шаблону
     * @param  string  $css  Содержимое CSS файла
     * @param  string  $js  Содержимое JS файла
     */
    public function memoizeComponent(
        string $className,
        string $dir,
        string $templatePath,
        string $css,
        string $js,
    ): void {
        $this->classCache[$className] = [
            'dir' => $dir,
            'templatePath' => $templatePath,
            'css' => $css,
            'js' => $js,
        ];
    }

    /**
     * @param  string  $className
     * @return array{dir: string, templatePath: string, css: string, js: string}|null
     */
    public function getMemoizedComponent(string $className): ?array
    {
        return $this->classCache[$className] ?? null;
    }

    public function isComponentMemoized(string $className): bool
    {
        return isset($this->classCache[$className]);
    }

    // ==================== Управление асетами ====================

    public function renderCss(): string
    {
        $cssAssets = [];
        foreach ($this->classCache as $className => $data) {
            if ($data['css'] !== '') {
                $cssAssets[$className] = $data['css'];
            }
        }
        if ($cssAssets === []) {
            return '';
        }

        return $this->assetRenderer->renderCss($cssAssets);
    }

    public function renderJs(): string
    {
        $jsAssets = [];
        foreach ($this->classCache as $className => $data) {
            if ($data['js'] !== '') {
                $jsAssets[$className] = $data['js'];
            }
        }
        if ($jsAssets === []) {
            return '';
        }

        return $this->assetRenderer->renderJs($jsAssets);
    }

    public function renderAssets(): string
    {
        $css = $this->renderCss();
        $js = $this->renderJs();

        if ($css === '' && $js === '') {
            return '';
        }

        if ($css !== '' && $js !== '') {
            return $css."\n".$js;
        }

        return $css.$js;
    }

    // ==================== Утилиты ====================

    /**
     * Очищает кэш зарегистрированных компонентов
     */
    public function clear(): void
    {
        $this->classCache = [];
    }

    /**
     * @return array{cached_classes: int, css_assets: int, js_assets: int}
     */
    public function getStats(): array
    {
        $result = array_reduce($this->classCache, function ($carry, $item) {
            return [
                'css' => $carry['css'] + (int)($item['css'] !== ''),
                'js' => $carry['js'] + (int)($item['js'] !== ''),
            ];
        }, ['css' => 0, 'js' => 0]);
        return [
            'cached_classes' => count($this->classCache),
            'css_assets' => $result['css'],
            'js_assets' => $result['js'],
        ];
    }

    /**
     * @return array<string, array{dir: string, templatePath: string, css: string, js: string}>
     */
    public function getFullInfo(): array
    {
        return $this->classCache;
    }
}
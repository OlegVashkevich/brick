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
     * CSS Асеты всех компонентов
     * @var array<string, string>
     */
    private array $cssAssets = [];

    /**
     * JS Асеты всех компонентов
     * @var array<string, string>
     */
    private array $jsAssets = [];

    /**
     * Кэш данных компонентов
     * @var array<string, array{dir: string, templatePath: string, css: string, js: string}>
     */
    private array $classCache = [];

    private static ?CacheInterface $cache = null;
    public static string $cachePrefix = 'brick_';
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

    public function cacheComponent(
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

        if ($css !== '') {
            $this->cssAssets[$className] = $css;
        }

        if ($js !== '') {
            $this->jsAssets[$className] = $js;
        }
    }

    /**
     * @param  string  $className
     * @return array{dir: string, templatePath: string, css: string, js: string}|null
     */
    public function getCachedComponent(string $className): ?array
    {
        return $this->classCache[$className] ?? null;
    }

    public function isComponentCached(string $className): bool
    {
        return isset($this->classCache[$className]);
    }

    // ==================== Управление асетами ====================

    public function renderCss(): string
    {
        if ($this->cssAssets === []) {
            return '';
        }

        return $this->assetRenderer->renderCss($this->cssAssets);
    }

    public function renderJs(): string
    {
        if ($this->jsAssets === []) {
            return '';
        }

        return $this->assetRenderer->renderJs($this->jsAssets);
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

    public function clear(): void
    {
        $this->cssAssets = [];
        $this->jsAssets = [];
        $this->classCache = [];
    }

    /**
     * @return array{cached_classes: int, css_assets: int, js_assets: int}
     */
    public function getStats(): array
    {
        return [
            'cached_classes' => count($this->classCache),
            'css_assets' => count($this->cssAssets),
            'js_assets' => count($this->jsAssets),
        ];
    }
}
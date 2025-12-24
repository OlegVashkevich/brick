<?php


declare(strict_types=1);

namespace OlegV\Traits;

use JsonException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use Throwable;

trait WithCache
{
    private static ?CacheInterface $cache = null;
    protected static string $cachePrefix = 'brick_';
    protected static int $cacheTtl = 3600;

    protected string $templatePath = '';

    /**
     * Устанавливает PSR-16 кэш для всех компонентов
     */
    public static function setCache(CacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * Рендерит компонент с кэшированием
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function render(): string
    {
        // Если кэш не настроен - обычный рендер
        if (!self::$cache) {
            return $this->renderOriginal();
        }

        // Генерируем ключ кэша
        $cacheKey = self::$cachePrefix.static::class.'_'.$this->getCacheHash();

        // Пробуем получить из кэша
        $cached = self::$cache->get($cacheKey);
        if ($cached !== null) {
            return (string)$cached;
        }

        // Рендерим и сохраняем в кэш
        $html = $this->renderOriginal();
        self::$cache->set($cacheKey, $html, self::$cacheTtl);

        return $html;
    }

    /**
     * Вызывает оригинальный метод render
     */
    private function renderOriginal(): string
    {
        return (function () {
            ob_start();

            try {
                include $this->templatePath;
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

            return (string)ob_get_clean();
        })->call($this);
    }

    /**
     * Генерирует хэш для ключа кэша
     * По умолчанию использует все публичные свойства
     * Компоненты могут переопределить этот метод
     * @throws JsonException
     */
    protected function getCacheHash(): string
    {
        // Быстрое получение всех публичных свойств
        return md5(json_encode(get_object_vars($this), JSON_THROW_ON_ERROR));
    }
}
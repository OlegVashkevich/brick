<?php


declare(strict_types=1);

namespace OlegV\Traits;

use JsonException;
use OlegV\BrickManager;
use Psr\SimpleCache\InvalidArgumentException;

trait WithCache
{
    /**
     * Внутреннее хранилище TTL
     */
    readonly private int $ttl;

    /**
     * Рендерит компонент с кэшированием
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function render(): string
    {
        $cache = BrickManager::getCache();
        // Если кэш не настроен - обычный рендер
        if ($cache===null) {
            return $this->renderOriginal();
        }

        // Генерируем ключ кэша
        $cacheKey = BrickManager::$cachePrefix.static::class.'_'.$this->getCacheHash();

        // Пробуем получить из кэша
        $cached = $cache->get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        // Рендерим и сохраняем в кэш
        $html = $this->renderOriginal();
        $cache->set($cacheKey, $html, $this->getTtl());

        return $html;
    }

    /**
     * Вызывает оригинальный метод render
     */
    private function renderOriginal(): string
    {
        return (function () {
            return parent::render();
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

    protected function getTtl(): int {
        if(!isset($this->ttl)) {
            $this->ttl = BrickManager::$cacheTtl;
        }
        return $this->ttl;
    }
}
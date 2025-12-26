<?php


declare(strict_types=1);

namespace OlegV\Traits;

use JsonException;
use OlegV\BrickManager;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use Throwable;

trait WithCache
{


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
        $cache->set($cacheKey, $html, BrickManager::$cacheTtl);

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
                $className = static::class;
                $manager = BrickManager::getInstance();
                $cached = $manager->getCachedComponent($className);
                include $cached['templatePath'];
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
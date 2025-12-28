<?php

declare(strict_types=1);

namespace OlegV\Traits;

use JsonException;
use OlegV\BrickManager;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Трейт для кэширования рендеринга компонентов
 *
 * Автоматически кэширует результат рендеринга компонента с учётом его свойств.
 * Использует PSR-16 Simple Cache для прозрачного кэширования.
 *
 * Как работает:
 * 1. Генерирует уникальный ключ кэша на основе всех публичных свойств компонента
 * 2. Проверяет наличие закэшированного результата
 * 3. Если кэш найден - возвращает его
 * 4. Если нет - рендерит, кэширует и возвращает результат
 *
 * Приоритет времени кэширования (TTL) от высшего к низшему:
 * 1. Динамический TTL, переданный в render($ttl) - самый высокий приоритет
 * 2. TTL из переопределённого метода ttl() в классе компонента
 * 3. Глобальный TTL из BrickManager::$cacheTtl
 *
 * Примеры:
 * // 1. Динамический TTL (приоритет 1)
 * echo (new MyComponent())->render(720); // TTL = 720 секунд
 *
 * // 2. TTL из метода класса (приоритет 2)
 * echo (new MyComponent())->render(); // TTL = 3600 (из метода ttl())
 *
 * // 3. Глобальный TTL (приоритет 3)
 * BrickManager::$cacheTtl = 1800; // 30 минут
 * echo (new MyComponent())->render(); // TTL = 1800
 *
 * Конфигурация:
 * - Настройте кэш через BrickManager::setCache()
 * - Установите глобальный TTL через BrickManager::$cacheTtl
 *
 * @example
 * class MyComponent extends Brick {
 *     use WithCache;
 *
 *     // Опционально переопределите TTL
 *     protected function ttl(): int {
 *         return 3600; // 1 час
 *     }
 * }
 */
trait WithCache
{
    /**
     * Рендерит компонент с кэшированием
     * @param  int|null  $ttl  Можно передать TTL для этого конкретного рендера
     * @return string
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function render(?int $ttl = null): string
    {
        // render может быть уже переопределен родителем с WithCache,
        // но если текущий класс без него - то кэш нам не нужен
        // поэтому проверяем, явно ли используется WithCache в текущем классе
        $currentClassTraits = class_uses($this);
        if (!in_array(WithCache::class, $currentClassTraits, true)) {
            return $this->renderOriginal();
        }

        $cache = BrickManager::getCache();
        $finalTtl = $ttl ?? $this->ttl();

        // Если кэш не настроен - обычный рендер
        if ($cache === null || $finalTtl === 0) {
            return $this->renderOriginal();
        }

        // Генерируем ключ кэша (учитываем TTL в хэше)
        $cacheKey = BrickManager::$cachePrefix.static::class.'_'.$this->getCacheHash().'_'.$finalTtl;

        // Пробуем получить из кэша
        $cached = $cache->get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        // Рендерим и сохраняем в кэш
        $html = $this->renderOriginal();
        $cache->set($cacheKey, $html, $finalTtl);

        return $html;
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

    /**
     * Переопределите в своем компоненте
     * если для него требуется уникальное время кэширования
     * Пример:
     * protected function ttl(): int
     * {
     *      return 600; // Кастомный TTL
     * }
     * @return int
     */
    protected function ttl(): int
    {
        return BrickManager::$cacheTtl;
    }
}
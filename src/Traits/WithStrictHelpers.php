<?php

declare(strict_types=1);

namespace OlegV\Traits;

/**
 * Трейт строгих проверок типов для шаблонов
 *
 * Решает две основные проблемы в шаблонах:
 * 1. Type safety - строгие проверки для nullable значений
 * 2. PHPStan compliance - устранение ошибок статического анализа
 *
 * Предоставляет методы для безопасной работы с потенциально null значениями:
 * - Проверка существования (has*)
 * - Получение со значением по умолчанию (get*)
 * - Безопасные преобразования типов (to*)
 * - Сравнения с учётом null
 * - И другое
 */
trait WithStrictHelpers
{
    // ==================== STRING HELPERS ====================

    /**
     * Проверка, что строка не null и не пустая
     *
     * @param  string|null  $value
     * @return bool
     */
    public function hasString(?string $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * Получение строки или значения по умолчанию
     *
     * @param  string|null  $value
     * @param  string  $default
     * @return string
     */
    public function getString(?string $value, string $default = ''): string
    {
        return $this->hasString($value) ? (string)$value : $default;
    }

    /**
     * Проверка, что строка равна определённому значению
     *
     * @param  string|null  $value
     * @param  string  $expected
     * @return bool
     */
    public function stringEquals(?string $value, string $expected): bool
    {
        return $value === $expected;
    }

    /**
     * Проверка, что строка содержит подстроку
     *
     * @param  string|null  $haystack
     * @param  string  $needle
     * @return bool
     */
    public function stringContains(?string $haystack, string $needle): bool
    {
        if (!$this->hasString($haystack)) {
            return false;
        }

        return str_contains((string)$haystack, $needle);
    }

    // ==================== NUMBER HELPERS ====================

    /**
     * Проверка, что число не null
     *
     * @param  int|float|null  $value
     * @return bool
     */
    public function hasNumber(int|float|null $value): bool
    {
        return $value !== null;
    }

    /**
     * Получение числа или значения по умолчанию
     *
     * @param  int|float|null  $value
     * @param  int|float  $default
     * @return int|float
     */
    public function getNumber(int|float|null $value, int|float $default = 0): int|float
    {
        return $value ?? $default;
    }

    /**
     * Проверка, что число больше нуля
     *
     * @param  int|float|null  $value
     * @return bool
     */
    public function isPositive(int|float|null $value): bool
    {
        return $value !== null && $value > 0;
    }

    // ==================== ARRAY HELPERS ====================

    /**
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * Проверка, что массив не null и не пустой
     *
     * @param  array<mixed>|null  $value
     * @return bool
     */
    public function hasArray(?array $value): bool
    {
        return $value !== null && $value !== [];
    }

    /**
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * Получение массива или пустого массива по умолчанию
     *
     * @param  array<mixed>|null  $value
     * @return array<mixed>
     */
    public function getArray(?array $value): array
    {
        return $value ?? [];
    }

    /**
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * Проверка, что ключ существует в массиве
     *
     * @param  array<mixed>|null  $array
     * @param  string|int  $key
     * @return bool
     */
    public function arrayHasKey(?array $array, string|int $key): bool
    {
        if ($array === null) {
            return false;
        }

        return array_key_exists($key, $array);
    }

    /**
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * Безопасное получение значения из массива
     *
     * @template T
     * @param  array<mixed>|null  $array
     * @param  string|int  $key
     * @param  T  $default
     * @return T|mixed
     */
    public function arrayGet(?array $array, string|int $key, mixed $default = null): mixed
    {
        if ($array === null || !array_key_exists($key, $array)) {
            return $default;
        }

        return $array[$key];
    }

    // ==================== BOOLEAN HELPERS ====================

    /**
     * Проверка булевого значения (учитывает что свойство может быть не bool)
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isTrue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        if (is_object($value)) {
            return true; // Непустой объект считается true
        }

        return false;
    }

    /**
     * Проверка, что значение false (строгая проверка типа)
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isFalse(mixed $value): bool
    {
        return $value === false;
    }

    // ==================== NULLABLE OBJECT HELPERS ====================

    /**
     * Проверка, что объект не null
     *
     * @param  object|null  $value
     * @return bool
     */
    public function hasObject(?object $value): bool
    {
        return $value !== null;
    }

    // ==================== TYPE CASTING HELPERS ====================

    /**
     * Безопасное приведение к строке
     *
     * @param  mixed  $value
     * @return string
     */
    public function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return '';
    }

    /**
     * Безопасное приведение к целому числу
     *
     * @param  mixed  $value
     * @param  int  $default
     * @return int
     */
    public function toInt(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $default;
    }

    /**
     * Безопасное приведение к float
     *
     * @param  mixed  $value
     * @param  float  $default
     * @return float
     */
    public function toFloat(mixed $value, float $default = 0.0): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float)$value;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        return $default;
    }

    /**
     * Безопасное приведение к bool
     *
     * @param  mixed  $value
     * @return bool
     */
    public function toBool(mixed $value): bool
    {
        return $this->isTrue($value);
    }

    // ==================== COMPARISON HELPERS ====================

    /**
     * Проверка, что два значения равны (с учётом типов)
     *
     * @param  mixed  $a
     * @param  mixed  $b
     * @return bool
     */
    public function equals(mixed $a, mixed $b): bool
    {
        return $a === $b;
    }

    /**
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * Проверка что значение находится в массиве
     *
     * @param  mixed  $value
     * @param  array<mixed>  $array
     * @return bool
     *
     */
    public function inArray(mixed $value, array $array): bool
    {
        return in_array($value, $array, true);
    }
}
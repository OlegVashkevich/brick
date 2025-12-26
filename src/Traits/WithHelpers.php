<?php

namespace OlegV\Traits;

use DateTime;
use DateTimeInterface;
use Exception;

/**
 * Трейт с расширенным набором вспомогательных методов для шаблонов
 *
 * Предоставляет базовые утилиты для работы с HTML, CSS классами, атрибутами
 * TODO: тесты и phpstan
 */
trait WithHelpers {

    // ==================== BASE ====================

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

    /**
     * Создание строки CSS классов из массива
     *
     * @param array<string|array<string, bool>> $classes Массив классов или условие => класс
     * @example class="<?= $this->classList(['btn', 'btn-primary']) ?>"
     * @example class="<?= $this->classList(['btn' => true, 'active' => $isActive]) ?>"
     */
    public function classList(array $classes): string
    {
        $result = [];

        foreach ($classes as $key => $value) {
            if (is_int($key)) {
                // Простой класс
                if (is_string($value) && $value !== '') {
                    $result[] = $value;
                }
            } else {
                // Условный класс
                if ($value && is_string($key) && $key !== '') {
                    $result[] = $key;
                }
            }
        }

        return implode(' ', array_unique($result));
    }

    /**
     * Форматирование HTML атрибутов из массива
     *
     * @param array<string, string|int|bool|null> $attributes
     * @example <?= $this->attr(['id' => 'btn', 'data-value' => $value]) ?>
     */
    public function attr(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $parts[] = $this->e($name);
            } elseif (is_scalar($value)) {
                $parts[] = sprintf('%s="%s"', $this->e($name), $this->e((string)$value));
            }
        }

        return implode(' ', $parts);
    }

    // ==================== FORMAT ====================

    /**
     * Форматирование числа с разделителями тысяч
     *
     * @param  float|int  $number Число для форматирования
     * @param int $decimals Количество знаков после запятой
     * @param string $decimalSeparator Разделитель дробной части
     * @param string $thousandsSeparator Разделитель тысяч
     */
    public function number(
        float|int $number,
        int $decimals = 0,
        string $decimalSeparator = ',',
        string $thousandsSeparator = ' '
    ): string {
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Форматирование даты
     *
     * @param  DateTimeInterface|int|string  $date  Дата для форматирования
     * @param  string  $format  Формат даты
     */
    public function date(DateTimeInterface|int|string $date, string $format = 'd.m.Y'): string
    {
        try {
            if (is_string($date) && trim($date) === '') {
                return ''; // Пустая строка - возвращаем пустую строку
            }

            if (is_numeric($date)) {
                $date = new DateTime('@' . $date);
            } elseif (is_string($date)) {
                $date = new DateTime($date);
            }

            if (!$date instanceof DateTimeInterface) {
                return '';
            }

            return $date->format($format);
        } catch (Exception) {
            // Ловим любые исключения DateTime и возвращаем пустую строку
            return '';
        }
    }

    /**
     * JSON кодирование с экранированием для JavaScript
     *
     * @param mixed $data Данные для кодирования
     */
    public function json(mixed $data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

    // ==================== FORMAT ====================

    /**
     * Обрезание строки до указанной длины с добавлением суффикса
     *
     * @param string $text Текст для обрезки
     * @param int $length Максимальная длина
     * @param string $suffix Суффикс для обрезанного текста
     */
    public function truncate(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    /**
     * Создание URL с query параметрами
     *
     * @param string $baseUrl Базовый URL
     * @param array<string, string|int|bool|null> $params Query параметры
     */
    public function url(string $baseUrl, array $params = []): string
    {
        // Разделяем URL и query строку
        $parsed = parse_url($baseUrl);

        if ($parsed === false) {
            return htmlspecialchars($baseUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Получаем существующие параметры из query строки
        $existingParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $existingParams);
        }

        // Объединяем существующие параметры с новыми (новые перезаписывают существующие)
        $allParams = array_merge($existingParams, $params);

        // Фильтруем null значения
        $filteredParams = array_filter($allParams, fn($value) => $value !== null);

        // Строим новую query строку
        $query = empty($filteredParams) ? '' : '?' . http_build_query($filteredParams);

        // Собираем URL обратно
        $result = '';
        if (isset($parsed['scheme'])) {
            $result .= $parsed['scheme'] . '://';
        }
        if (isset($parsed['host'])) {
            $result .= $parsed['host'];
        }
        if (isset($parsed['port'])) {
            $result .= ':' . $parsed['port'];
        }
        if (isset($parsed['path'])) {
            $result .= $parsed['path'];
        }
        $result .= $query;
        if (isset($parsed['fragment'])) {
            $result .= '#' . $parsed['fragment'];
        }

        return htmlspecialchars($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Генерация уникального ID (для использования в HTML)
     *
     * @param string $prefix Префикс для ID
     */
    public function uniqueId(string $prefix = 'id_'): string
    {
        static $counter = 0;
        return $prefix . (++$counter);
    }

    /**
     * Подсчет слов в строке
     *
     * @param  string  $text
     * @return int
     */
    public function wordCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        setlocale(LC_ALL, 'ru_RU.UTF-8');
        return str_word_count($text, 0, 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ');
    }

    /**
     * Создание строки с учетом множественного числа
     *
     * @param int $count Количество
     * @param array{0: string, 1: string, 2: string} $forms Формы слова для разных чисел
     * @example $this->plural(5, ['комментарий', 'комментария', 'комментариев'])
     */
    public function plural(int $count, array $forms): string
    {
        $absCount = abs($count);
        $cases = [2, 0, 1, 1, 1, 2];
        $index = ($absCount % 100 > 4 && $absCount % 100 < 20)
            ? 2
            : $cases[min($absCount % 10, 5)];

        return $forms[$index];
    }
}
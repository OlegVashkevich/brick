<?php

declare(strict_types=1);

namespace OlegV;

/**
 * Базовый иммутабельный класс для HTML-компонентов
 *
 * Компонент состоит из 4 файлов в одной папке:
 * 1. ИмяКласса.php   - PHP класс с публичными свойствами
 * 2. template.php    - HTML шаблон
 * 3. style.css       - Стили (опционально)
 * 4. script.js       - JavaScript (опционально)
 *
 *  Для добавления функциональности в компоненты используйте трейты:
 *  - WithCache - включает кэширование рендера
 *  - WithHelpers - для вспомогательных методов
 *  - WithStrictHelpers - для строгих проверок типов для шаблонов
 *  - WithInheritance - для наследования шаблонов и асетов
 *
 *
 * ✅ Рекомендуемый вариант для всех UI-компонентов.
 *
 * Иммутабельность обеспечивает:
 * - Предсказуемый HTML-рендеринг
 * - Безопасность в многопоточной среде
 * - Простое кэширование на уровне компонента
 * - Легкое тестирование и отладку
 *
 * @example
 * // Button/Button.php
 * class Button extends Brick {
 *     public function __construct(
 *         public string $text,
 *         public string $variant = 'primary'
 *     ) {
 *         parent::__construct(); // Автоматически находит файлы в Button/
 *     }
 * }
 *
 * // Использование
 * echo new Button('Нажми меня', 'primary');
 *
 * @see Clay Мутабельный вариант (только для особых случаев)
 */
abstract readonly class Brick
{
    use Mold;
}

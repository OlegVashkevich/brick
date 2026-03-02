# Brick

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-tested-366C9C?style=flat&logo=php&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-level%20MAX-8E44AD?style=flat&logo=php&logoColor=white)
![PSR-12](https://img.shields.io/badge/code%20style-PSR--12-1E90FF?style=flat&logo=php&logoColor=white)
![Tests](https://img.shields.io/github/actions/workflow/status/OlegVashkevich/brick/tests.yml?label=Tests)
![Analise](https://img.shields.io/github/actions/workflow/status/OlegVashkevich/brick/stan.yml?label=Analise)
![License](https://img.shields.io/github/license/OlegVashkevich/brick?style=flat)
![Immutable Components](https://img.shields.io/badge/Components-Immutable-blueviolet)
![Server-side](https://img.shields.io/badge/Rendering-Server--side-blue)
![No Dependencies](https://img.shields.io/badge/Dependencies-Minimal-success)

**Строго типизированные UI-компоненты для PHP — без фреймворков, без магии, без overhead.**

Brick приносит компонентный подход в PHP: каждый UI-элемент — это `readonly` класс с типизированными свойствами, HTML-шаблоном, стилями и скриптами в одной директории. Контракт компонента виден в IDE, проверяется PHPStan и не ломается молча при рефакторинге.

---

## Установка

```bash
composer require olegv/brick
```

---

## Быстрый старт

```php
// HelloWorld/HelloWorld.php
class HelloWorld extends Brick
{
    public function __construct(
        public string $name,
    ) {}
}
```

```php
// HelloWorld/template.php
/** @var HelloWorld $this */
?>
<h1>Hello, <?= $this->e($this->name) ?>!</h1>
```

```php
echo new HelloWorld('World');
echo BrickManager::getInstance()->renderAssets();
```

---
## Рекомендации по использованию
- **Делайте композицию компонентов по возможности через `echo` или приведение к строке**
- **Оставьте конструктор пустым, а подготовку данных выполняйте в `prepare()`** - он автоматически срабатывает перед подключением шаблона
- **Используйте простые типы данных** — `string`, `int`, `float`, `bool`, `array` и `null`

---

## Документация

- [Философия и архитектура](docs/01-philosophy.md)
- [Быстрый старт](docs/02-getting-started.md)
- [Компоненты](docs/03-components.md)
- [Трейты](docs/04-traits.md)
- [Управление ассетами](docs/05-assets.md)
- [Тестирование и статический анализ](docs/06-testing.md)
- [Дизайн-система и переносимость](docs/07-design-system.md)
- [Интеграции и сравнение с аналогами](docs/08-integrations.md)

---

## Требования

- PHP 8.2+
- PSR-16 совместимая библиотека кэша (опционально, для `WithCache`)

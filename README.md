# Brick UI Component System

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

> **Внимание!** Эта библиотека является экспериментальной и предназначена для исследовательских целей.

**Brick** — инструмент для создания строго типизированных UI-компонентов на PHP. Каждый компонент — это иммутабельный `readonly` PHP-класс, который автоматически связывается со своим HTML-шаблоном, CSS-стилями и JavaScript-кодом.

## Ключевые особенности

*   **Иммутабельные компоненты (`Brick`)**: `readonly` классы, которые не меняются после создания.
*   **Автоматическое обнаружение файлов**: Brick сам находит `template.php`, `style.css`, `script.js` в папке компонента.
*   **Централизованное управление (`BrickManager`)**: Кэширование метаданных компонентов, рендеринг CSS/JS.
*   **Модульность через трейты**: Добавляйте кэширование (`WithCache`), хелперы (`WithHelpers`) и другие возможности.
*   **Безопасность по умолчанию**: Автоматическое экранирование HTML, защита от XSS.

## Рекомендации по использованию
- **Делайте композицию компонентов только через `echo` или приведение к строке**
- **Не используйте исключения (`throw`) внутри компонентов**
- Если необходимо — логируйте ошибки, но не прерывайте выполнение

## Быстрый старт

### Установка

```bash
composer require olegv/brick
```

### Минимальный пример

```php
<?php
// HelloWorld/HelloWorld.php
declare(strict_types=1);
namespace PSR4Path\HelloWorld;

readonly class HelloWorld extends \OlegV\Brick
{
    public function __construct(public string $name) {
        parent::__construct();
    }
}
```

```php
<?php
//HelloWorld/template.php
declare(strict_types=1);
use PSR4Path\HelloWorld\HelloWorld;
/** @var HelloWorld $this */
?>
<!-- HelloWorld/template.php -->
<h1>Hello, <?= $this->e($this->name) ?>!</h1>
```

```php
<?php
// Использование
require_once 'vendor/autoload.php';
echo new HelloWorld('World');
echo \OlegV\BrickManager::getInstance()->renderAssets();
```

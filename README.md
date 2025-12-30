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

**Brick** — это следующий шаг в эволюции UI-разработки на PHP. Для сложных, долгоживущих проектов, где важны строгие контракты и предсказуемость, обычные шаблонизаторы (Twig, Blade) оставляют нерешёнными фундаментальные проблемы: IDE не знает, какие переменные доступны в шаблоне; ошибки типов обнаруживаются только в рантайме; тестирование вёрстки требует хрупких E2E-тестов; кэширование превращается в рутину.

**Система Brick** превращает создание интерфейсов из хаотичного процесса в строго типизированную, предсказуемую среду. Представьте компоненты как математические функции: одинаковые входные данные всегда дают одинаковый HTML-вывод — никаких скрытых состояний, никаких побочных эффектов.

**Автономность компонентов:** Библиотеки компонентов Brick — это самодостаточная UI-единица, которую можно переносить из проекта в проект. Создавайте собственные компоненты как Composer-пакеты и используйте их в разных приложениях без переписывания.

**Как это работает?** Каждый компонент Brick — это иммутабельный `readonly` PHP-класс с промоутед-свойствами. Это явный контракт: IDE точно знает структуру данных, PHPStan проверяет типы до запуска, автодополнение работает из коробки. Класс автоматически связывается со своим HTML-шаблоном, CSS-стилями и JavaScript-кодом — вы описываете **что** должно отобразиться, а Brick заботится о **как**.

**Что это даёт на практике:**
- **Чёткие контракты** вместо "угадай, какие переменные нужны шаблону"
- **Статический анализ UI** вместо runtime-ошибок
- **Юнит-тесты вёрстки** вместо хрупких E2E
- **Автоматическое кэширование** с PSR-16 вместо ручного управления
- **Типизированная композиция** вместо наследования с побочными эффектами
- **Безопасность по умолчанию** — автоматическое экранирование, защита от XSS

**Идеология проекта:** UI-компоненты должны быть простыми как кирпичи (Brick), но из них можно строить сложные интерфейсы. Не шаблоны с вкраплениями PHP, а чистый код с декларативным описанием. Не монолитные виджеты, а композиция маленьких, независимых компонентов. Не ручное управление ассетами, а автоматическое обнаружение и оптимизация через `BrickManager`.

## Ключевые особенности

*   **Иммутабельные компоненты (`Brick`)**: `readonly` классы, которые не меняются после создания. Одинаковые входные данные = одинаковый HTML-вывод.
*   **Автоматическое обнаружение файлов**: Brick сам находит `template.php`, `style.css`, `script.js` в папке компонента.
*   **Полное разделение логики и представления**: PHP-класс, HTML-шаблон, CSS и JS хранятся отдельно, но связаны автоматически.
*   **Централизованное управление (`BrickManager`)**: Кэширование метаданных компонентов, рендеринг CSS/JS ассетов, сбор статистики.
*   **Модульность через трейты**: Легко добавляйте кэширование (`WithCache`), хелперы (`WithHelpers`), строгие проверки типов (`WithStrictHelpers`) или наследование (`WithInheritance`).
*   **Гибкие стратегии ассетов**: Встраивайте CSS/JS прямо в страницу (`InlineAssetRenderer`) или генерируйте отдельные файлы (`FileAssetRenderer`).
*   **Безопасность по умолчанию**: Автоматическое экранирование HTML, защита от XSS в атрибутах.
*   **Строгая типобезопасность**: PHP 8.2+, `strict_types=1`, полная поддержка статического анализа (PHPStan MAX).

## Документация

| Раздел | Описание |
|--------|----------|
| [Начало работы](doc/getting-started.md) | Установка и создание первого компонента |
| [Компоненты](doc/components.md) | Brick, Clay, жизненный цикл, шаблоны |
| [Трейты](doc/traits.md) | WithCache, WithHelpers, WithStrictHelpers, WithInheritance |
| [Ассеты](doc/assets.md) | CSS/JS, AssetRenderer, создание своих рендереров |
| [Продвинутые сценарии](doc/advanced.md) | Оптимизация, интеграция, тестирование, безопасность |

## Быстрый старт

### Установка

```bash
composer require olegv/brick
```

### Минимальный пример

```php
// HelloWorld/HelloWorld.php
readonly class HelloWorld extends \OlegV\Brick
{
    public function __construct(public string $name) {
        parent::__construct();
    }
}
```

```php
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
---

**Brick** — это современный подход к созданию UI на PHP: предсказуемые, типизированные, иммутабельные компоненты с полной поддержкой статического анализа. Начните создавать интерфейсы как код уже сегодня!

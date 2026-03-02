# Дизайн-система и переносимость

## Компонент как самодостаточная единица

Каждый Brick-компонент несёт всё необходимое в своей директории — класс, шаблон, стили, скрипты. Нет зависимостей на глобальные переменные, нет предположений об окружении. Это означает что компонент можно взять и перенести в другой проект без изменений.

Это свойство — не случайность, а следствие архитектуры. И оно открывает один очень практичный сценарий.

---

## UI-кит как composer-пакет

Если у вас несколько PHP-проектов, имеет смысл вынести общие компоненты в отдельный приватный пакет.

### Структура пакета

```
company/ui-kit/
  src/
    Button/
      Button.php
      template.php
      style.css
      script.js
    Card/
      Card.php
      template.php
      style.css
    Modal/
      Modal.php
      template.php
      style.css
      script.js
    Form/
      Input/
        Input.php
        template.php
        style.css
      Select/
        ...
  tests/
    Button/
      ButtonTest.php
    Card/
      CardTest.php
  composer.json
```

### composer.json пакета

```json
{
    "name": "company/ui-kit",
    "description": "Общие UI-компоненты компании",
    "type": "library",
    "require": {
        "php": "^8.2",
        "olegv/brick": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.0",
        "phpstan/phpstan": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Company\\UI\\": "src/"
        }
    }
}
```

### Подключение в проекте

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:company/ui-kit.git"
        }
    ],
    "require": {
        "company/ui-kit": "^1.0"
    }
}
```

```php
use Company\UI\Button\Button;
use Company\UI\Card\Card;

echo new Card(
    title: 'Заголовок',
    body: 'Текст',
    action: new Button(text: 'Подробнее'),
);
```

---

## Версионирование и контракт

Здесь строгий контракт раскрывает своё главное долгосрочное преимущество.

Когда контракт компонента меняется — это **явная несовместимость**, а не тихая ошибка в рантайме. Добавили обязательное свойство в `Button` — все проекты которые его используют сразу узнают об этом через `composer update`. PHP-компилятор покажет каждое место где нужно внести изменения.

```php
// Было в версии 1.0
class Button extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary',
    ) {}
}

// Стало в версии 2.0 — добавили обязательный size
class Button extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary',
        public string $size = 'md', // опциональное — обратная совместимость сохранена
    ) {}
}
```

Опциональные свойства со значением по умолчанию не ломают обратную совместимость. Новые обязательные свойства — это мажорная версия.

### Семантическое версионирование

```
1.0.0 → 1.0.1  Исправление бага в шаблоне или стилях
1.0.0 → 1.1.0  Новый компонент или опциональное свойство
1.0.0 → 2.0.0  Изменение контракта — новое обязательное свойство или удаление свойства
```

---

## Тесты как гарантия стабильности

Пакет с компонентами должен иметь полное покрытие тестами. Это не просто хорошая практика — это то что делает пакет безопасным для обновления.

```php
// tests/Button/ButtonTest.php
class ButtonTest extends TestCase
{
    public function test_renders_text(): void
    {
        $this->assertStringContainsString(
            'Купить',
            (new Button(text: 'Купить'))->render()
        );
    }

    public function test_all_variants_render(): void
    {
        foreach (['primary', 'secondary', 'danger', 'ghost'] as $variant) {
            $html = (new Button(text: 'OK', variant: $variant))->render();
            $this->assertStringContainsString("btn--$variant", $html);
        }
    }
}
```

CI-пайплайн пакета запускает тесты и PHPStan при каждом коммите. Если что-то сломалось — узнаёте до того как изменения попадут в проекты.

### GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: vendor/bin/phpunit
      - run: vendor/bin/phpstan analyse
```

---

## Наследование в дизайн-системе

`WithInheritance` особенно полезен в контексте дизайн-системы. Базовые компоненты определяют структуру и базовые стили, а специализированные — расширяют их:

```
src/
  BaseCard/
    BaseCard.php
    template.php      ← общий шаблон для всех карточек
    style.css         ← базовые стили карточки

  ProductCard/
    ProductCard.php   ← use WithInheritance
    style.css         ← стили специфичные для товара

  ArticleCard/
    ArticleCard.php   ← use WithInheritance
    style.css         ← стили специфичные для статьи
    template.php      ← переопределяет шаблон BaseCard
```

```php
class BaseCard extends Brick
{
    public function __construct(
        public string $title,
        public string $body,
    ) {}
}

class ProductCard extends BaseCard
{
    use WithInheritance;

    public function __construct(
        string $title,
        string $body,
        public int $price,
        public string $sku = '',
    ) {
        parent::__construct($title, $body);
    }
}
```

Итоговый CSS `ProductCard` = `BaseCard/style.css` + `ProductCard/style.css`. Шаблон берётся из `BaseCard` если у `ProductCard` нет своего.

---

## Документирование компонентов

Хороший пакет компонентов — это живая документация. Docblock-комментарии в классе видны прямо в IDE:

```php
/**
 * Универсальная кнопка.
 *
 * @example
 * echo new Button(text: 'Сохранить');
 * echo new Button(text: 'Удалить', variant: 'danger', size: 'sm');
 */
class Button extends Brick
{
    /**
     * @param string $text    Текст кнопки
     * @param string $variant Стиль: primary | secondary | danger | ghost
     * @param string $size    Размер: sm | md | lg
     * @param bool   $disabled Отключить кнопку
     */
    public function __construct(
        public string $text,
        public string $variant = 'primary',
        public string $size = 'md',
        public bool $disabled = false,
    ) {}
}
```

---

## Что дальше

- [Интеграции и сравнение с аналогами](08-integrations.md)

# Трейты: Расширение функциональности компонентов

Трейты в Brick — это модульный способ добавления функциональности к компонентам. Каждый трейт решает одну конкретную задачу.
## ⚠️ Важное замечание

**Трейты `WithCache` и `WithInheritance` не наследуются автоматически!**

Если базовый класс использует эти трейты, производные классы **должны явно подключать их**:

```php
// ❌ НЕ РАБОТАЕТ: трейты не наследуются
readonly class BaseComponent extends Brick
{
    use WithCache; // Трейт подключен в родителе
}

class ChildComponent extends BaseComponent
{
    // WithCache НЕ доступен здесь!
}
// ✅ ПРАВИЛЬНО: явное подключение в каждом классе
readonly class BaseComponent extends Brick
{
    use WithCache;
}

class ChildComponent extends BaseComponent
{
    use WithCache; // Явно подключаем снова
}
```

## Обзор трейтов

| Трейт | Назначение | Когда использовать |Наследуется?|
|-------|------------|-------------------|------------|
| `WithCache` | Кэширование рендеринга | Для компонентов с тяжёлым рендерингом |❌ Нет|
| `WithHelpers` | Вспомогательные методы | Для удобной работы в шаблонах |✅ Да|
| `WithStrictHelpers` | Строгие проверки типов | Для максимальной типобезопасности |✅ Да|
| `WithInheritance` | Наследование компонентов | Для создания иерархий компонентов |❌ Нет|

## WithCache: кэширование рендеринга

Автоматически кэширует результат рендеринга с использованием PSR-16 кэша.

### Настройка

```php
use OlegV\BrickManager;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

BrickManager::setCache(new FilesystemAdapter(__DIR__ . '/cache'));
BrickManager::$cacheTtl = 1800; // 30 минут по умолчанию
```

### Использование

```php
readonly class Article extends \OlegV\Brick
{
    use \OlegV\Traits\WithCache;
    
    public function __construct(
        public string $title,
        public string $content
    ) {
        parent::__construct();
    }
    
    // Опционально: кастомный TTL
    protected function ttl(): int
    {
        return 3600; // 1 час для этого компонента
    }
}

// С глобальным TTL (1800 секунд)
echo $article->render();

// С динамическим TTL (7200 секунд)
echo $article->render(7200);
```

### Приоритет TTL:
1. Динамический (в `render($ttl)`)
2. Класса (из метода `ttl()`)
3. Глобальный (из `BrickManager::$cacheTtl`)

## WithHelpers: вспомогательные методы

Предоставляет набор полезных методов для работы в шаблонах.

### Основные методы

```php
// template.php

<!-- HTML и атрибуты -->
<div class="<?= $this->classList([
    'btn',
    'btn-primary' => $this->isPrimary,
    'disabled' => $this->isDisabled
]) ?>"
     <?= $this->attr([
        'id' => 'button-' . $this->id,
        'data-action' => $this->action,
     ]) ?>>
</div>

<!-- Форматирование данных -->
<p>
    Дата: <?= $this->date($this->createdAt, 'd.m.Y H:i') ?><br>
    Число: <?= $this->number($this->price, 2, ',', ' ') ?> ₽<br>
    Обрезанный текст: <?= $this->truncate($this->description, 100) ?>
</p>

<!-- URL и строки -->
<a href="<?= $this->url('/products', ['category' => $this->categoryId]) ?>">
    Ссылка
</a>

<p>
    <?= $this->count ?> 
    <?= $this->plural($this->count, ['комментарий', 'комментария', 'комментариев']) ?>
</p>
```

### Полный список методов

| Метод | Описание |
|-------|----------|
| `e(?string $value): string` | HTML экранирование |
| `classList(array $classes): string` | CSS классы из массива |
| `attr(array $attributes): string` | HTML атрибуты |
| `date($date, string $format): string` | Форматирование даты |
| `number($num, int $decimals, ...): string` | Форматирование числа |
| `json(mixed $data): string` | JSON с экранированием |
| `truncate(string $text, int $length): string` | Обрезка строки |
| `url(string $baseUrl, array $params): string` | Генерация URL |
| `uniqueId(string $prefix): string` | Уникальный ID |
| `plural(int $count, array $forms): string` | Множественное число |

## WithStrictHelpers: строгие проверки типов

Решает проблемы типобезопасности в шаблонах, особенно с nullable значениями.

```php
// template.php

<!-- Безопасная работа со строками -->
<?php if ($this->hasString($this->title)): ?>
    <h1><?= $this->e($this->getString($this->title)) ?></h1>
<?php else: ?>
    <h1>Без названия</h1>
<?php endif; ?>

<!-- Безопасная работа с массивами -->
<?php if ($this->hasArray($this->tags)): ?>
    Теги: <?= implode(', ', $this->getArray($this->tags)) ?>
<?php endif; ?>

<!-- Безопасное получение из массива -->
<?= $this->arrayGet($this->config, 'theme', 'light') ?>
```

### Основные методы

| Категория | Методы |
|-----------|--------|
| **Проверки** | `hasString()`, `hasNumber()`, `hasArray()` |
| **Получение** | `getString()`, `getNumber()`, `getArray()` |
| **Преобразования** | `toString()`, `toInt()`, `toFloat()` |
| **Работа с массивами** | `arrayGet()`, `arrayHasKey()` |

## WithInheritance: наследование компонентов

Позволяет создавать иерархии компонентов с наследованием шаблонов и ассетов.

### Пример

**Базовый компонент (`BaseCard`):**
```php
readonly class BaseCard extends \OlegV\Brick
{
    public function __construct(
        public string $title,
        public string $content
    ) {
        parent::__construct();
    }
}
```

**Производный компонент (`ProductCard`):**
```php
readonly class ProductCard extends BaseCard
{
    use \OlegV\Traits\WithInheritance;
    
    public function __construct(
        string $title,
        string $content,
        public float $price
    ) {
        parent::__construct($title, $content);
    }
}
```

### Что наследуется автоматически

1. **Шаблоны**: используется первый найденный в цепочке наследования
2. **CSS**: добавляет все `style.css` от родителя к потомку
3. **JS**: добавляет все `script.js` от родителя к потомку

### Порядок поиска:
- `ProductCard/template.php` → не найден
- `BaseCard/template.php` → найден, используется

---

**Далее:** Узнайте о [рендеринге ассетов](assets.md) и создании собственных `AssetRenderer`

# Трейты

Brick минималистичен по умолчанию. Дополнительная функциональность подключается через трейты — только там где нужна, только тем компонентам которым это необходимо.

---

## WithCache

Кэширует результат рендеринга компонента. Использует PSR-16 Simple Cache — подходит любая совместимая библиотека: Redis, Memcached, файловый кэш.

### Настройка

```php
// Один раз при инициализации приложения
use OlegV\BrickManager;

BrickManager::setCache(new PredisCachePool(new \Predis\Client()));
BrickManager::$cacheTtl = 3600; // глобальный TTL по умолчанию
```

### Использование

```php
class ProductCard extends Brick
{
    use WithCache;

    public function __construct(
        public string $title,
        public int $price,
        public string $imageUrl = '',
    ) {}
}
```

Этого достаточно. Первый рендер выполнится и сохранится в кэше, повторный вызов с теми же данными вернёт результат из кэша без выполнения шаблона.

### Управление TTL

TTL определяется по приоритету от высшего к низшему:

**1. Динамический TTL при вызове** — подходит когда TTL зависит от контекста:

```php
echo (new ProductCard(title: 'Товар', price: 990))->render(ttl: 300);
```

**2. TTL в методе класса** — когда компоненту нужно своё время кэширования:

```php
class ProductCard extends Brick
{
    use WithCache;

    public function __construct(
        public string $title,
        public int $price,
    ) {}

    protected function ttl(): int
    {
        return 600; // 10 минут для этого компонента
    }
}
```

**3. Глобальный TTL** — применяется если не задан более конкретный:

```php
BrickManager::$cacheTtl = 3600; // 1 час для всех компонентов с WithCache
```

Чтобы отключить кэш для конкретного компонента — верните `0` из метода `ttl()`.

### Как формируется ключ кэша

Ключ строится автоматически из всех публичных свойств компонента через `md5(json_encode(...))`. Разные данные → разный ключ → отдельная запись в кэше. Явная инвалидация при смене данных не нужна.

Если стандартной логики недостаточно — переопределите `getCacheHash()`:

```php
protected function getCacheHash(): string
{
    // Кэшируем только по ID, игнорируя вспомогательные поля
    return md5((string)$this->productId);
}
```

---

## WithHelpers

Расширенный набор утилит для шаблонов. Покрывает типичные задачи: форматирование, работа со строками, генерация атрибутов.

```php
class Card extends Brick
{
    use WithHelpers;
    // ...
}
```

### HTML и атрибуты

**`e(?string $value)`** — экранирование HTML. Доступен во всех компонентах без трейта, но `WithHelpers` предоставляет расширенную версию.

```php
<?= $this->e($this->title) ?>
```

**`attr(array $attributes)`** — генерация HTML-атрибутов из массива. Автоматически пропускает `null` и `false`, экранирует значения, блокирует `javascript:` в URL-атрибутах и обработчики событий `on*`.

```php
<div <?= $this->attr([
    'id'         => $this->id,
    'class'      => 'card',
    'data-value' => $this->value,
    'hidden'     => $this->isHidden,  // false — атрибут не выведется
]) ?>>
```

**`classList(array $classes)`** — формирование строки CSS-классов с условиями:

```php
<button class="<?= $this->classList([
    'btn',
    'btn--primary'         => $this->variant === 'primary',
    'btn--danger'          => $this->variant === 'danger',
    'btn--disabled'        => !$this->enabled,
]) ?>">
```

### Форматирование

**`number(float|int $number, int $decimals, string $dec, string $thousands)`** — числа с разделителями:

```php
<?= $this->number(1234567, 2) ?>  // 1 234 567,00
```

**`date(DateTimeInterface|int|string $date, string $format)`** — форматирование дат:

```php
<?= $this->date($this->createdAt, 'd.m.Y') ?>
<?= $this->date('2024-01-15', 'd MMMM Y') ?>
<?= $this->date(1705276800) ?> // из Unix timestamp
```

**`json(mixed $data)`** — JSON для использования в JavaScript с корректным экранированием:

```php
<div data-config="<?= $this->json($this->config) ?>">
```

### Строки

**`truncate(string $text, int $length, string $suffix)`** — обрезка с учётом многобайтовых символов:

```php
<?= $this->truncate($this->description, 150) ?>
```

**`plural(int $count, array $forms)`** — склонение по числу (для русского языка):

```php
<?= $count . ' ' . $this->plural($count, ['товар', 'товара', 'товаров']) ?>
// 1 товар, 3 товара, 11 товаров
```

**`wordCount(string $text)`** — подсчёт слов с поддержкой кириллицы.

### URL и ID

**`url(string $baseUrl, array $params)`** — сборка URL с query-параметрами:

```php
<a href="<?= $this->url('/catalog', ['page' => 2, 'sort' => 'price']) ?>">
// /catalog?page=2&sort=price
```

**`uniqueId(string $prefix)`** — уникальный ID для связки `label` и `input`:

```php
<?php $inputId = $this->uniqueId('field_') ?>
<label for="<?= $inputId ?>">Название</label>
<input id="<?= $inputId ?>" type="text">
```

---

## WithStrictHelpers

Строгие проверки типов для работы с nullable-значениями. Устраняет ошибки PHPStan при работе с `?string`, `?int`, `?array` в шаблонах.

```php
class UserProfile extends Brick
{
    use WithStrictHelpers;

    public function __construct(
        public string $name,
        public ?string $bio = null,
        public ?array $tags = null,
    ) {}
}
```

### Строки

```php
// Проверка что строка не null и не пустая
$this->hasString($this->bio)          // bool

// Получение со значением по умолчанию
$this->getString($this->bio, 'Нет описания')  // string

// Проверка содержимого
$this->stringEquals($this->role, 'admin')     // bool
$this->stringContains($this->bio, 'PHP')      // bool
```

### Числа

```php
$this->hasNumber($this->price)           // bool — не null
$this->isPositive($this->price)          // bool — больше нуля
$this->getNumber($this->price, 0)        // int|float со значением по умолчанию
```

### Массивы

```php
$this->hasArray($this->tags)             // bool — не null и не пустой
$this->getArray($this->tags)             // array — или пустой массив
$this->arrayHasKey($this->tags, 'php')   // bool
$this->arrayGet($this->tags, 'php', '')  // значение или default
```

### Приведение типов

```php
$this->toString($value)     // безопасное приведение к string
$this->toInt($value)        // безопасное приведение к int
$this->toFloat($value)      // безопасное приведение к float
$this->toBool($value)       // безопасное приведение к bool
```

### Сравнение

```php
$this->equals($a, $b)               // строгое сравнение ===
$this->inArray($value, $array)      // строгий in_array
$this->isTrue($value)               // гибкая проверка на истинность
```

---

## WithInheritance

Наследование шаблонов и ассетов по цепочке классов. Позволяет строить иерархии компонентов не дублируя разметку.

### Пример

Есть базовый компонент `BaseCard` с шаблоном и стилями. `ProductCard` наследует его и добавляет свои стили:

```
BaseCard/
  BaseCard.php
  template.php    ← базовый шаблон
  style.css       ← базовые стили

ProductCard/
  ProductCard.php
  style.css       ← дополнительные стили (шаблона нет — используется BaseCard)
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
    ) {
        parent::__construct($title, $body);
    }
}
```

`ProductCard` автоматически получит шаблон из `BaseCard`, а его CSS будет объединён: сначала `BaseCard/style.css`, затем `ProductCard/style.css`.

### Логика поиска

- **Шаблон**: берётся из ближайшего предка у которого есть `template.php`
- **CSS и JS**: объединяются все файлы по цепочке от родителя к потомку

Это позволяет переопределять шаблон на любом уровне иерархии — достаточно создать `template.php` в директории потомка.

---

## Комбинирование трейтов

Трейты можно комбинировать:

```php
class ProductCard extends Brick
{
    use WithCache;
    use WithHelpers;
    use WithInheritance;

    public function __construct(
        public string $title,
        public int $price,
    ) {}

    protected function ttl(): int { return 600; }
}
```

---

## Что дальше

- [Управление ассетами — рендереры, Vite, S3](05-assets.md)
- [Тестирование и статический анализ](06-testing.md)

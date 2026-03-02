# Компоненты

## Brick и Clay

Brick предоставляет два базовых класса. Выбор между ними — осознанное архитектурное решение.

**`Brick`** — иммутабельный `readonly` класс. Рекомендуемый вариант для всех UI-компонентов. Одинаковые данные всегда дают одинаковый HTML, рендер безопасно кэшируется, тесты предсказуемы.

```php
abstract readonly class Brick { ... }

class Button extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary',
    ) {}
}
```

**`Clay`** — мутабельный аналог. Существует для особых случаев: сложное внутреннее состояние, интеграция с легаси-кодом. Нарушает детерминированность рендера, усложняет кэширование и тестирование. Используйте только когда `Brick` действительно не подходит.

```php
abstract class Clay { ... }

// Только для особых случаев
class LegacyWidget extends Clay { ... }
```

---

## Структура компонента

Каждый компонент — директория с четырьмя файлами. `template.php` обязателен, остальные опциональны.

```
Button/
  Button.php      ← обязательно
  template.php    ← обязательно
  style.css       ← опционально
  script.js       ← опционально
```

Brick находит файлы автоматически через Reflection — по расположению класса на диске. Никакой явной регистрации не требуется.

---

## Шаблон

Шаблон — обычный PHP-файл. `$this` внутри шаблона — это экземпляр компонента. Аннотация `@var` даёт IDE и PHPStan полное понимание типов:

```php
// Button/template.php
declare(strict_types=1);

use App\Components\Button\Button;

/** @var Button $this */
?>
<button class="btn btn--<?= $this->e($this->variant) ?>">
    <?= $this->e($this->text) ?>
</button>
```

Метод `$this->e()` экранирует HTML-спецсимволы и защищает от XSS. Используйте его для всех пользовательских данных.

---

## Хук prepare()

Перед подключением шаблона автоматически вызывается метод `prepare()`. Он предназначен для лёгких преобразований уже готовых данных — форматирование, вычисление производных значений, подготовка структур для шаблона.

```php
class PriceTag extends Brick
{
    public string $formattedPrice = '';
    public string $discountLabel = '';

    public function __construct(
        public int $price,
        public ?int $oldPrice = null,
        public string $currency = '₽',
    ) {}

    protected function prepare(): void
    {
        $this->formattedPrice = number_format($this->price, 0, ',', ' ') . ' ' . $this->currency;

        if ($this->oldPrice !== null && $this->oldPrice > $this->price) {
            $discount = (int)round(100 - ($this->price / $this->oldPrice * 100));
            $this->discountLabel = '-' . $discount . '%';
        }
    }
}
```

```php
// PriceTag/template.php
/** @var PriceTag $this */
?>
<div class="price">
    <span class="price__current"><?= $this->e($this->formattedPrice) ?></span>
    <?php if ($this->discountLabel): ?>
        <span class="price__discount"><?= $this->e($this->discountLabel) ?></span>
    <?php endif ?>
</div>
```

⚠️ `prepare()` — не место для запросов в БД или вызовов API. Данные должны приходить снаружи через конструктор. Компонент отображает данные, а не получает их.

---

## Вложенные компоненты

Компоненты рендерятся в строку через `__toString()`, поэтому любой компонент может содержать другой — просто передайте его как свойство.

```php
class Card extends Brick
{
    public function __construct(
        public string $title,
        public string $body,
        public Button $action,   // вложенный компонент — часть контракта
        public ?Badge $badge = null,
    ) {}
}
```

```php
// Card/template.php
/** @var Card $this */
?>
<div class="card">
    <?php if ($this->badge): ?>
        <?= $this->badge ?>
    <?php endif ?>
    <h2><?= $this->e($this->title) ?></h2>
    <p><?= $this->e($this->body) ?></p>
    <div class="card__footer">
        <?= $this->action ?>
    </div>
</div>
```

```php
echo new Card(
    title: 'Название товара',
    body: 'Описание товара',
    action: new Button(text: 'В корзину'),
    badge: new Badge(text: 'Новинка', color: 'green'),
);
```

Вложенный компонент — часть контракта класса. IDE знает тип, PHPStan проверяет. Никакого `slot`, никаких строк с разметкой — просто объект как свойство.

CSS и JS вложенных компонентов автоматически попадают в `BrickManager` при рендере и выводятся вместе с остальными ассетами страницы.

---

## Списки компонентов

Массив компонентов передаётся как обычное свойство:

```php
class ProductList extends Brick
{
    /**
     * @param ProductCard[] $items
     */
    public function __construct(
        public array $items,
        public string $title = '',
    ) {}
}
```

```php
// ProductList/template.php
/** @var ProductList $this */
?>
<section class="product-list">
    <?php if ($this->title): ?>
        <h2><?= $this->e($this->title) ?></h2>
    <?php endif ?>
    <div class="product-list__grid">
        <?php foreach ($this->items as $item): ?>
            <?= $item ?>
        <?php endforeach ?>
    </div>
</section>
```

```php
echo new ProductList(
    title: 'Популярные товары',
    items: array_map(
        fn($product) => new ProductCard(
            title: $product->title,
            price: $product->price,
        ),
        $products,
    ),
);
```

---

## Обработка ошибок

Brick не бросает исключения в `__toString()` — PHP это запрещает. Вместо этого поведение при ошибке зависит от окружения.

В режиме отладки компонент показывает подробное сообщение прямо на странице:

```
🚨 Brick Render Error
Ошибка рендеринга компонента App\Components\Card: ...
```

В продакшене ошибка тихо логируется, пользователь видит HTML-комментарий:

```html
<!-- Brick render error -->
```

Переключение режимов:

```php
BrickManager::enableDebug();  // включить отладку
BrickManager::disableDebug(); // выключить

// Или через переменные окружения
// APP_ENV=development
// APP_DEBUG=true
```

---

## Что дальше

- [Трейты — кэширование, хелперы, наследование](04-traits.md)
- [Управление ассетами — рендереры, Vite, S3](05-assets.md)
- [Тестирование и статический анализ](06-testing.md)

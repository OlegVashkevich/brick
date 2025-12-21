# 🧱 Brick - Базовый PHP класс для UI-компонентов

Brick — это каркас для создания строго типизированных UI-компонентов в PHP, спроектированный для разработчиков, которые предпочитают явные контракты, статический анализ и чистую архитектуру вместо шаблонных движков и runtime-магии. Каждый компонент — это строго типизированный класс с детерминированным поведением.

## Особенности

- ✅ **Строгая типизация** - PHP 8.2 именованные аргументы - типы проверяются на этапе компиляции, а не в runtime
- ✅ **Автоматическое обнаружение файлов** - класс, шаблон, стили и JS вместе
- ✅ **Наследование компонентов** - расширяйте с кастомными шаблонами
- ✅ **Композиция** - вложенные компоненты с типизацией
- ✅ **Управление ассетами** - CSS/JS автоматически собираются
- ✅ **Оптимизация ассетов** - кэширование и дедупликация стилей и скриптов
- ✅ **PHPStan уровня max** - Полная поддержка статического анализа без подавления ошибок
- ✅ **PHPUnit** - компоненты легко тестируются как обычные классы

## Установка

```bash
composer require olegv/brick
```

## Быстрый старт
### 1. Создайте компонент Button
Структура:

```
src/Components/Button/
├── Button.php      # Класс компонента
├── template.php    # HTML шаблон
├── style.css       # Стили компонента
└── script.js       # JavaScript (опционально)
```
Button.php:
```php
<?php

namespace Components\Button;

use OlegV\Brick;

class Button extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary',
        public ?string $url = null,
        public bool $disabled = false,
    ) {
        parent::__construct(); // Автоматически находит файлы в папке Button/
    }
}
```
template.php:
```php
<?php
/** @var \Components\Button\Button $this */
$tag = $this->url && !$this->disabled ? 'a' : 'button';
?>

<<?= $tag ?>
    class="btn btn-<?= $this->e($this->variant) ?>"
    <?php if ($this->url && !$this->disabled): ?>
        href="<?= $this->e($this->url) ?>"
    <?php endif; ?>
    <?php if ($this->disabled && $tag === 'button'): ?>
        disabled
    <?php endif; ?>
>
    <?= $this->e($this->text) ?>
</<?= $tag ?>>
```
style.css:
```css
.btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}
```
### 2. Создайте компонент ProductCard с Button
ProductCard.php:
```php
<?php

namespace Components\ProductCard;

use OlegV\Brick;
use Components\Button\Button;

class ProductCard extends Brick
{
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public string $imageUrl,
        public Button $button, // ← Типизированная композиция!
    ) {
        parent::__construct();
    }
}
```
### 3. Используйте компоненты
```php
<?php

require 'vendor/autoload.php';

use Components\Button\Button;
use Components\ProductCard\ProductCard;

// Создаем кнопку
$button = new Button('Купить', 'primary');

// Создаем карточку товара с кнопкой
$product = new ProductCard(
    id: 1,
    title: 'Беспроводные наушники',
    price: 89.99,
    imageUrl: '/img/headphones.jpg',
    button: $button
);

// Рендерим
echo $product;

// Выводим все CSS/JS из использованных компонентов
echo OlegV\Brick::renderAssets();
```
## Наследование компонентов
Расширяйте компоненты с собственными шаблонами:

ExtendedButton/ExtendedButton.php:
```php
<?php

namespace Components\ExtendedButton;

use Components\Button\Button;

class ExtendedButton extends Button
{
    public function __construct(
        string $text,
        public ?string $icon = null, // Новое свойство
        string $variant = 'primary',
    ) {
        parent::__construct($text, $variant);
        // Автоматически использует ExtendedButton/template.php
    }
}
```
ExtendedButton/template.php (кастомный шаблон):
```php
<?php
/** @var \Components\ExtendedButton\ExtendedButton $this */
$tag = $this->url && !$this->disabled ? 'a' : 'button';
?>

<<?= $tag ?> class="btn extended">
    <?php if ($this->icon): ?>
        <i class="icon"><?= $this->e($this->icon) ?></i>
    <?php endif; ?>
    <?= $this->e($this->text) ?>
</<?= $tag ?>>
```
## Глубокая вложенность компонентов
```php
<?php

$page = new Page(
    header: new Header(
        navigation: new Navigation(
            items: [
                new NavItem('Главная', '/'),
                new NavItem('Товары', '/products'),
            ]
        )
    ),
    content: new ProductGrid(
        products: [
            new ProductCard(...),
            new ProductCard(...),
        ]
    )
);
```
## API
### Методы компонента
```php
$this->render();       // Рендерит в HTML
(string) $this;        // То же самое что render()
```
### Статические методы
```php
Brick::renderCss();         // Рендерит все CSS
Brick::renderJs();          // Рендерит все JS
Brick::renderAssets();      // Рендерит CSS + JS
Brick::clear();             // Очищает ассеты (для тестов)
```
### Хелперы в шаблонах
```php
$this->e($value);           // Экранирование HTML
$this->classList($classes); // Создание строки CSS классов
```
## Интеграция с Cement DI-контейнером

Brick отлично работает с [Cement](https://github.com/OlegVashkevich/cement) - DI-контейнером:

```php
use OlegV\Cement\Cement;
use Components\Button\Button;
use Components\ProductCard\ProductCard;

$cement = new Cement();

// Замешиваем компоненты
$cement->addAll([
    Button::class => [
        'buy' => fn($c) => new Button('Купить', 'primary'),
        'cart' => fn($c) => new Button('В корзину', 'secondary'),
    ],
    
    ProductCard::class =>  fn($c, $p) => new ProductCard(
        id: $p['id'] ?? 1,
        title: $p['title'] ?? 'Товар',
        price: $p['price'] ?? 99.99,
        imageUrl: $p['imageUrl'] ?? '/product.jpg',
        button: $p['button'] ?? $c->get(Button::class, ['variant' => 'buy'])
    ),
]);

// Автоматическое создание сложных компонентов
$products = [];

// Карточка по умолчанию
$products[] = $cement->get(ProductCard::class, [
    'title' => 'Товар 1',
    'price' => $cement->get(Price::class, ['amount' => 1500])
]);

// Карточка с другой ценой и кнопкой
$products[] = $cement->get(ProductCard::class, [
    'title' => 'iPhone 15',
    'description' => 'Новый смартфон Apple',
    'price' => new Price(120000, '₽'),
    'image' => $cement->get(Image::class, [
        'src' => '/images/iphone.jpg',
        'alt' => 'iPhone 15'
    ])
]);

// Компактная карточка
$products[] = $cement->get(ProductCard::class, [
    'title' => 'Ноутбук',
    'variant' => 'compact',
    'price' => $cement->get(Price::class, ['amount' => 45000])
]);

// 4. Рендеринг
foreach ($products as $product) {
    echo $product->render();
}
```
---
Brick — это подход `UI как код`: компоненты становятся частью типизированной кодовой базы, а не шаблонным слоем вне статического анализа. Вы проектируете интерфейсы с четким контрактом, а не просто пишете HTML. Минимальная абстракция, максимальная предсказуемость. 
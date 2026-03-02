# Быстрый старт

## Установка

```bash
composer require olegv/brick
```

---

## Первый компонент

Создадим простую карточку. Структура директорий:

```
src/
  Components/
    Card/
      Card.php
      template.php
      style.css
```

### Класс компонента

```php
// src/Components/Card/Card.php
declare(strict_types=1);

namespace App\Components\Card;

use OlegV\Brick;

class Card extends Brick
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $imageUrl = null,
    ) {}
}
```

### Шаблон

Аннотация `@var` даёт IDE и PHPStan понимание типов внутри шаблона — автодополнение свойств и методов работает как в обычном PHP-классе.

```php
// src/Components/Card/template.php
declare(strict_types=1);

use App\Components\Card\Card;

/** @var Card $this */
?>
<div class="card">
    <?php if ($this->imageUrl): ?>
        <img src="<?= $this->e($this->imageUrl) ?>" alt="">
    <?php endif ?>
    <h2><?= $this->e($this->title) ?></h2>
    <p><?= $this->e($this->body) ?></p>
</div>
```

### Стили

```css
/* src/Components/Card/style.css */
.card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.5rem;
    background: #fff;
}

.card img {
    width: 100%;
    border-radius: 4px;
    margin-bottom: 1rem;
}
```

### Использование

```php
require_once 'vendor/autoload.php';

use App\Components\Card\Card;
use OlegV\BrickManager;

// Рендер компонента
echo new Card(
    title: 'Привет, Brick!',
    body: 'Это мой первый компонент.',
);

// CSS и JS всех компонентов на странице — один раз в конце
echo BrickManager::getInstance()->renderAssets();
```

Brick автоматически находит `template.php`, `style.css` и `script.js` в директории класса. Никакой регистрации, никаких конфигов.

---

## Структура типичного проекта

```
src/
  Components/
    Button/
      Button.php
      template.php
      style.css
      script.js
    Card/
      Card.php
      template.php
      style.css
    Navigation/
      Navigation.php
      template.php
      style.css
      script.js
```

Компоненты можно группировать как угодно — по фичам, по типу, по странице. Brick не накладывает ограничений на структуру проекта.

---

## Полная страница

Типичный сценарий — компоненты рендерятся в теле страницы, ассеты выводятся один раз:

```php
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пример</title>
</head>
<body>

    <?= new Navigation(items: $menuItems) ?>

    <main>
        <?php foreach ($products as $product): ?>
            <?= new ProductCard(
                title: $product->title,
                price: $product->price,
                action: new Button(text: 'Купить'),
            ) ?>
        <?php endforeach ?>
    </main>

</body>

<!-- Весь CSS и JS всех компонентов — одним блоком, без дублей -->
<?= BrickManager::getInstance()->renderAssets() ?>

</html>
```

`BrickManager` отслеживает какие компоненты были использованы на странице и выводит только их ассеты.

---

## Что дальше

- [Компоненты — подробно о Brick, Clay, prepare() и вложенности](03-components.md)
- [Трейты — кэширование, хелперы, наследование](04-traits.md)
- [Управление ассетами — рендереры, Vite, S3](05-assets.md)

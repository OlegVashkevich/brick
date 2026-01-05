# Начало работы с Brick UI

Это руководство поможет вам установить библиотеку Brick и создать первый компонент за 5 минут.

## Требования

- PHP 8.2 или выше
- Composer

## Установка

```bash
composer require olegv/brick
```

## Структура компонента

Каждый компонент Brick состоит из 4 файлов в одной папке:

```
ComponentName/              # Папка компонента (имя произвольное)
├── ComponentName.php       # PHP класс компонента (обязательно)
├── template.php           # HTML шаблон (обязательно)
├── style.css              # CSS стили (опционально)
└── script.js              # JavaScript код (опционально)
```

Библиотека автоматически находит и связывает эти файлы.

## Первый компонент: Кнопка

### 1. Создайте папку компонента

```
src/UI/Button/
```

### 2. Класс компонента (`Button.php`)

```php
<?php
// src/UI/Button/Button.php

namespace YourApp\UI;

use OlegV\Brick;

readonly class Button extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary',
        public bool $disabled = false
    ) {
        parent::__construct(); // Важно: всегда вызывайте родительский конструктор
    }
}
```

### 3. HTML шаблон (`template.php`)

```php
<?php
// src/UI/Button/template.php
?>
<button 
    class="btn btn-<?= $this->e($this->variant) ?><?= $this->disabled ? ' disabled' : '' ?>"
    <?= $this->disabled ? 'disabled' : '' ?>
>
    <?= $this->e($this->text) ?>
</button>
```

### 4. CSS стили (`style.css`)

```css
/* src/UI/Button/style.css */

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
```

## Использование

### 1. Подключите автозагрузку

```php
<?php
// index.php
require_once __DIR__ . '/vendor/autoload.php';

use YourApp\UI\Button;

// Простое использование
echo new Button('Нажми меня');
echo new Button('Отмена', 'secondary');
echo new Button('Недоступно', 'primary', true);
```

### 2. Рендеринг ассетов

```php
<?php
// В <head> вашей страницы
echo \OlegV\BrickManager::getInstance()->renderCss();

// Перед закрывающим </body>
echo \OlegV\BrickManager::getInstance()->renderJs();

// Или всё сразу
echo \OlegV\BrickManager::getInstance()->renderAssets();
```

## Настройка автозагрузки

Добавьте в `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "YourApp\\": "src/"
        }
    }
}
```

Затем выполните:
```bash
composer dump-autoload
```

## Структура проекта

```
my-project/
├── src/
│   └── UI/
│       ├── Button/
│       │   ├── Button.php
│       │   ├── template.php
│       │   └── style.css
│       ├── Card/
│       └── ...
├── vendor/
├── public/
│   └── index.php
├── composer.json
└── README.md
```

## Далее

- Узнайте больше о [компонентах Brick и Clay](components.md)
- Используйте [трейты для расширения функциональности](traits.md)
- Настройте [рендеринг ассетов](assets.md) под свои нужды
- Изучите [продвинутые сценарии](advanced.md) использования
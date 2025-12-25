# Установка и настройка

## Требования

- PHP 8.1 или выше
- Composer
- Любая структура проекта (монолит, модули, пакет)

---

## Установка

```bash
composer require olegv/brick
```

Или добавьте вручную в `composer.json`:

```json
{
    "require": {
        "olegv/brick": "^0.1"
    }
}
```

---

## Настройка

Brick не требует сложной конфигурации. По умолчанию всё готово к работе.

### 1. Автозагрузка

Если вы используете Composer — ничего делать не нужно.

### 2. Структура проекта

Создайте директорию для компонентов. Например:

```
src/
└── Components/
    ├── Button/
    ├── Card/
    └── ...
```

Рекомендуем размещать компоненты рядом с бизнес-логикой, а не в отдельном изолированном слое.

### 3. Регистрация компонентов

Ничего регистрировать не нужно. Brick автоматически обнаруживает компоненты при их первом использовании.

---

## Первый запуск

Создайте простой компонент для проверки:

```
src/Components/Test/
├── Test.php
└── template.php
```

**Test.php:**
```php
<?php

namespace App\Components;

use OlegV\Brick;

class Test extends Brick
{
    public function __construct(
        public string $message = 'Hello Brick'
    ) {}
}
```

**template.php:**
```php
<div class="test">
    <?= $this->e($this->message) ?>
</div>
```

Теперь используйте его где угодно:

```php
use App\Components\Test;

$component = new Test('It works!');
echo $component->render();
```

---

## Дополнительные настройки

### Ассеты

По умолчанию CSS и JS встраиваются в HTML. Чтобы вынести их в файлы:

```php
use OlegV\Brick;
use OlegV\Assets\FileAssetRenderer;

$renderer = new FileAssetRenderer(
    __DIR__ . '/public/assets',
    '/assets/',
    true // минификация
);

Brick::setAssetRenderer($renderer);
```

### Кэширование

Подключите PSR-16 совместимый кэш:

```php
use OlegV\Brick;
use OlegV\Traits\WithCache;

class MyComponent extends Brick
{
    use WithCache;
}

// Где-то в bootstrap:
Brick::setCache($psr16Cache);
```

---

## Философия настройки

Brick создан по принципу **«работает из коробки»**.  
Настройка нужна только если вы хотите изменить **поведение**, а не просто **использовать**.

> Лучшая настройка — та, которую не пришлось делать.

---

## Что дальше?

- [Создание первого компонента](./first-component.md) — подробнее о структуре и возможностях
- [Наследование и композиция](./inheritance.md) — как строить сложные UI из простых блоков
- [Работа с ассетами](./assets.md) — тонкости управления CSS и JavaScript

---

> Brick не навязывает архитектуру. Он предлагает инструменты.  
> Вы решаете, как их использовать.
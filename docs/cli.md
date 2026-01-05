# Brick CLI

Утилита для быстрого создания компонентов Brick UI.

## Использование

```bash
./vendor/bin/brick 'PSR4Namespace'
```

### Примеры

```bash
./vendor/bin/brick 'My\Components\Button'
./vendor/bin/brick 'App\UI\Forms\LoginForm'
./vendor/bin/brick 'Company\Project\Cards\ProductCard'
```

## Что создается

Для компонента `My\Components\Button`:

```
src/My/Components/Button/
├── Button.php          # PHP класс компонента
├── template.php       # HTML шаблон
├── style.css          # Стили компонента
└── script.js          # JavaScript код
```

## Требования

1. **Composer.json** с PSR-4 автозагрузчиком:
```json
{
    "autoload": {
        "psr-4": {
            "My\\Components\\": "src/My/Components/"
        }
    }
}
```

2. PHP 8.2+ с strict_types

## Особенности

- Интеграция с Composer PSR-4 маппингами
- Чистый PHP без зависимостей
- Проверка корректности имен классов
- Защита от перезаписи существующих файлов

## Пример созданного класса

```php
<?php
declare(strict_types=1);

namespace My\Components\Button;

use OlegV\Brick;

readonly class Button extends Brick
{
    public function __construct(
        public string $text,
        public string $variant = 'primary'
    ) {
        parent::__construct();
    }
}
```
## Пример созданного template.php
```php
<?php
declare(strict_types=1);
namespace My\Components\Button;
/** @var Button $this */
?>
<button class="btn btn-<?=$this->e($this->variant)?>">
    <?=$this->e($this->text)?>
</button>
```

## После создания

Обновите автозагрузку:

```bash
composer dump-autoload
```

## Использование в коде

```php
use My\Components\Button;
echo new Button('Нажми меня', 'primary');
```

## Ошибки и устранение

1. **"Не найдено PSR-4 соответствие"** – добавьте маппинг в composer.json
2. **"Директория уже существует"** – выберите другое имя компонента
3. **"Некорректное имя класса"** – используйте PSR-совместимые имена
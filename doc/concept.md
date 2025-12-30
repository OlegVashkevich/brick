# Concept - Brick UI Component System

## 1. Технологии
- **Тип:** Чистая PHP-библиотека для Composer
- **Версия PHP:** 8.2+
- **Установка:** `composer require olegv/brick`
- **Рендеринг:** Server-side через PHP шаблоны
- **База данных:** Не требуется
- **Веб-сервер:** Любой поддерживающий PHP
- **Контейнеризация:** Нативная установка, без Docker
- **Зависимости:** 
  - `psr/simple-cache`: ^3.0
  - `ext-mbstring`: *

## 2. Принцип разработки
- **Тестирование:** Стремление к 100% покрытию, юнит-тесты
- **CI/CD:** GitHub Actions для тестов и PHPStan
- **Версионирование:** SemVer через git tags
- **Документация:** README.md + docs/ + примеры
- **Code quality:** PSR-12, PHP-CS-Fixer, PHPStan level MAX
- **Минимализм:** KISS принцип, никакого оверинжиниринга

## 3. Структура проекта
```
brick/
├── src/                    # Исходный код библиотеки
│   ├── Assets/            # Рендереры ресурсов
│   ├── Traits/            # Все трейты
│   ├── Brick.php          # Основной класс
│   ├── Clay.php           # Мутабельная версия
│   ├── BrickManager.php   # Менеджер
│   └── Mold.php           # Базовый трейт
├── tests/                 # Юнит-тесты
├── docs/                  # Документация
├── composer.json          # Composer конфиг
├── phpunit.xml.dist       # Конфиг тестов
├── phpcs.xml.dist         # Code style
├── phpstan.neon.dist      # Статический анализ
└── README.md              # Основная документация
```

## 4. Архитектура проекта

### Визуальная схема
```
┌─────────────────────────────────────────────────────────┐
│                    Приложение (Application)             │
├─────────────────────────────────────────────────────────┤
│  echo new Button('Click');                              │
│  echo new Card($title, $content);                       │
└───────────────┬─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────────────┐
│                    Brick / Clay                         │
│  ┌─────────────────────────────────────────────────┐    │
│  │  Компоненты:                                    │    │
│  │  • Button     • Card      • Modal               │    │
│  │  • Form       • List      • ...                 │    │
│  └─────────────────────────────────────────────────┘    │
└───────────────┬─────────────────────────────────────────┘
                │ Использует трейты
                ▼
┌─────────────────────────────────────────────────────────┐
│                    Трейты (Traits)                      │
│  ┌─────────┐  ┌──────────┐  ┌─────────────┐  ┌───────┐  │
│  │ With    │  │ With     │  │ WithStrict  │  │ With  │  │
│  │ Cache   │  │ Helpers  │  │ Helpers     │  │ Inher.│  │
│  └─────────┘  └──────────┘  └─────────────┘  └───────┘  │
└───────────────┬─────────────────────────────────────────┘
                │ Основан на
                ▼
┌─────────────────────────────────────────────────────────┐
│                    Mold (Базовый трейт)                 │
│  • Автоматическая инициализация компонента              │
│  • Поиск template.php, style.css, script.js             │
│  • Базовый рендеринг                                    │
│  • HTML экранирование (метод e())                       │
└───────────────┬─────────────────────────────────────────┘
                │ Регистрирует в
                ▼
┌─────────────────────────────────────────────────────────┐
│                    BrickManager                         │
│  • Центральный менеджер компонентов                     │
│  • Кэширование метаданных компонентов                   │
│  • Рендеринг CSS/JS ассетов                             │
│  • Поддержка PSR-16 кэша                                │
│  • Статистика и мониторинг                              │
└───────────────┬─────────────────────────────────────────┘
                │ Использует
                ▼
┌─────────────────────────────────────────────────────────┐
│                    AssetRenderer                        │
│  ┌────────────────────┐  ┌──────────────────────────┐   │
│  │ InlineAssetRenderer│  │ FileAssetRenderer        │   │
│  │ • Встраивает в     │  │ • Генерирует файлы       │   │
│  │   HTML страницу    │  │ • Поддержка минификации  │   │
│  │ • Быстро, просто   │  │ • Режимы: single/multiple│   │
│  └────────────────────┘  └──────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### Описание потоков данных
1. **Создание компонента:**
```
new Button('Click') → Mold::__construct() →
BrickManager::memoizeComponent() → Загрузка файлов
```

2. **Рендеринг компонента:**
```
echo $button → Brick::__toString() →
WithCache::render() (если есть) →
Mold::renderOriginal() → template.php
```

3. **Работа с ассетами:**
```
BrickManager::renderAssets() →
AssetRenderer::renderCss()/renderJs() →
Inline или File рендеринг
```

## 5. Модель данных
- **Компоненты как DTO:** Публичные readonly свойства
- **Валидация:** На усмотрение разработчика компонента (в конструкторе)
- **Value Objects:** По необходимости, на усмотрение разработчика
- **Коллекции:** Простые массивы компонентов, без специальных классов
- **Сериализация:** Не реализуется на уровне библиотеки

## 6. Сценарии работы

### Базовый компонент (без WithInheritance)
```php
class Button extends Brick {
    public function __construct(
        public string $text,
        public string $variant = 'primary'
    ) {
        parent::__construct(); // Ищет Button/template.php
    }
}
// Требует Button/template.php
```

### Компонент с наследованием
```php
class ProductCard extends BaseCard {
    use \OlegV\Traits\WithInheritance;
    // Использует BaseCard/template.php если своего нет
    // Добавляет ProductCard/style.css и script.js
}
```

### Компонент с кэшированием
```php
// Настройка кэша
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
BrickManager::setCache(new FilesystemAdapter());
BrickManager::$cacheTtl = 3600;

class Article extends Brick {
    use \OlegV\Traits\WithCache;
    
    public function __construct(
        public string $title,
        public string $content
    ) {
        parent::__construct();
    }
    
    protected function ttl(): int {
        return 1800; // Кастомный TTL
    }
}

// Использование
echo (new Article('Title', 'Content'))->render(); // С кэшированием
echo (new Article('Title', 'Content'))->render(7200); // С динамическим TTL
```

### Работа с ассетами
```php
// Файловый рендерер
$renderer = new FileAssetRenderer(
    __DIR__ . '/public/assets',
    '/assets/',
    true, // минификация
    FileAssetRenderer::MODE_SINGLE
);

// Вывод всех CSS/JS
echo BrickManager::getInstance()->renderAssets();
```

## 7. Деплой
- **Публикация:** Packagist через GitHub
- **Требования:** PHP 8.2+
- **Инсталляция:** `composer require olegv/brick`
- **composer.json:** С поддержкой PSR-4 автозагрузки
- **Dev-зависимости:** PHPUnit, PHPStan, phpstan-strict-rules

## 8. Подход к конфигурированию
- **Метод:** Только через методы BrickManager
- **Без констант:** Для простоты и типизации
- **Без конфиг-файлов:** Конфигурация в коде приложения
- **Умолчания:** Работает без любой конфигурации

**Доступные настройки:**
```php
// Кэширование
BrickManager::setCache($psr16Cache);
BrickManager::$cacheTtl = 3600;
BrickManager::$cachePrefix = 'brick_';

// Рендерер ассетов
$renderer = new InlineAssetRenderer();
$manager = new BrickManager($renderer);
```

## 9. Подход к логгированию
- **Без логгирования:** Не добавляем зависимость от PSR-3
- **Отладка:** Через исключения с понятными сообщениями
- **Статистика:** BrickManager::getStats() для метрик
- **Мониторинг:** Исключения + статистика достаточно для отладки

## Основные ценности
1. **Предсказуемость:** Иммутабельные компоненты = детерминированный вывод
2. **Простота:** KISS принцип во всём
3. **Безопасность:** Автоматическое экранирование, защита от XSS
4. **Производительность:** Многоуровневое кэширование
5. **Типобезопасность:** PHP 8.2+ со strict_types
6. **Минимализм:** Только необходимое, ничего лишнего
```
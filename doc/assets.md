# Ассеты: CSS, JavaScript и AssetRenderer

Управление CSS и JavaScript — ключевая часть Brick. Система автоматически загружает, кэширует и рендерит ассеты компонентов.

## Обзор системы ассетов

### Как работают ассеты

1. **Загрузка**: При инициализации компонента Brick автоматически ищет `style.css` и `script.js` в его папке
2. **Кэширование**: CSS/JS содержимое кэшируется в `BrickManager`
3. **Рендеринг**: Используется настроенный `AssetRenderer`
4. **Вывод**: Генерируются HTML-теги (`<style>`, `<script>`, `<link>`)

### Структура компонента

```
Button/
├── Button.php          # Класс компонента
├── template.php        # HTML шаблон
├── style.css          # Стили (обнаруживается автоматически)
└── script.js          # JavaScript (обнаруживается автоматически)
```

## Встроенные рендереры

### InlineAssetRenderer (по умолчанию)

Встраивает CSS и JavaScript прямо в HTML страницу.

```php
use OlegV\BrickManager;
use OlegV\Assets\InlineAssetRenderer;

$renderer = new InlineAssetRenderer();
BrickManager::getInstance()->setAssetRenderer($renderer);
```

**Вывод:**
```html
<style>.btn { padding: 10px; }</style>
<script>console.log('Brick loaded');</script>
```

**Плюсы:** Нет HTTP-запросов, простая настройка  
**Минусы:** Увеличивает размер HTML, нет кэширования браузером

### FileAssetRenderer

Создаёт физические CSS и JS файлы на диск.

```php
use OlegV\Assets\FileAssetRenderer;

$renderer = new FileAssetRenderer(
    outputDir: __DIR__ . '/public/assets', // Куда сохранять
    publicUrl: '/assets/',                 // Публичный URL
    minify: true,                          // Минифицировать
    mode: FileAssetRenderer::MODE_SINGLE   // Все в один файл
);
```

**Вывод:**
```html
<link rel="stylesheet" href="/assets/brick.all.a1b2c3d4.css">
<script src="/assets/brick.all.e5f6g7h8.js"></script>
```

**Плюсы:** Кэширование браузером, минификация, CDN-совместимость  
**Минусы:** Создаёт файлы на диске, сложнее в разработке

## Настройка AssetRenderer

### Режимы работы

#### MODE_SINGLE (по умолчанию)
```php
$renderer->setMode(FileAssetRenderer::MODE_SINGLE);
```
Все CSS/JS всех компонентов объединяются в один файл.

#### MODE_MULTIPLE
```php
$renderer->setMode(FileAssetRenderer::MODE_MULTIPLE);
```
Каждый компонент получает отдельные CSS/JS файлы.

### Минификация

```php
// Включение минификации
$renderer->setMinify(true);

// Отключение минификации (по умолчанию в разработке)
$renderer->setMinify(false);
```

### Параметры FileAssetRenderer

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `outputDir` | `string` | — | Директория для сохранения файлов |
| `publicUrl` | `string` | `'/assets/'` | Публичный URL |
| `minify` | `bool` | `false` | Минификация |
| `mode` | `string` | `MODE_SINGLE` | Режим объединения |
| `filePrefix` | `string` | `'brick'` | Префикс имён файлов |

## Создание собственного AssetRenderer

### Базовый интерфейс

```php
namespace YourApp\Assets;

use OlegV\Assets\AbstractAssetRenderer;

class CustomAssetRenderer extends AbstractAssetRenderer
{
    public function __construct(
        private string $cdnUrl
    ) {
        $this->minify = true;
    }
    
    public function renderCss(array $cssAssets): string
    {
        if (empty($cssAssets)) {
            return '';
        }
        
        $processed = $this->processCssAssets($cssAssets);
        $css = $processed['all'] ?? implode("\n", $processed);
        $hash = md5($css);
        
        return sprintf(
            '<link rel="stylesheet" href="%s/styles-%s.css">',
            $this->cdnUrl,
            $hash
        );
    }
    
    public function renderJs(array $jsAssets): string
    {
        // Аналогично renderCss
    }
}
```

### Пример: S3AssetRenderer для Amazon S3

```php
namespace YourApp\Assets;

use Aws\S3\S3Client;
use OlegV\Assets\AbstractAssetRenderer;

class S3AssetRenderer extends AbstractAssetRenderer
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucket,
        private string $s3Path = 'assets/'
    ) {
        $this->minify = true;
        $this->mode = self::MODE_SINGLE;
    }
    
    public function renderCss(array $cssAssets): string
    {
        return $this->uploadAndGenerateLinks($cssAssets, 'css');
    }
    
    private function uploadAndGenerateLinks(array $assets, string $type): string
    {
        $content = $this->processAssets($assets, $type);
        $hash = md5($content);
        $filename = "brick-{$hash}.{$type}";
        
        // Загрузка в S3
        $this->s3Client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $this->s3Path . $filename,
            'Body' => $content,
            'ContentType' => "text/{$type}",
            'CacheControl' => 'public, max-age=31536000'
        ]);
        
        $url = $this->s3Client->getObjectUrl($this->bucket, $this->s3Path . $filename);
        
        return $type === 'css' 
            ? sprintf('<link rel="stylesheet" href="%s">', $url)
            : sprintf('<script src="%s"></script>', $url);
    }
}
```

## Продвинутые сценарии

### Ленивая загрузка ассетов

```php
namespace YourApp\Assets;

use OlegV\Assets\AbstractAssetRenderer;

class LazyAssetRenderer extends AbstractAssetRenderer
{
    private array $loadedComponents = [];
    
    public function registerComponent(string $componentClass): void
    {
        $this->loadedComponents[$componentClass] = true;
    }
    
    public function renderCss(array $cssAssets): string
    {
        // Фильтруем только ассеты используемых компонентов
        $usedAssets = array_intersect_key($cssAssets, $this->loadedComponents);
        return parent::renderCss($usedAssets);
    }
}
```

### Hot Module Replacement для разработки

```php
class HmrAssetRenderer extends AbstractAssetRenderer
{
    public function __construct(
        private string $hmrUrl = 'http://localhost:8080'
    ) {
        $this->minify = false;
    }
    
    public function renderCss(array $cssAssets): string
    {
        return sprintf('<link rel="stylesheet" href="%s/styles.css">', $this->hmrUrl);
    }
}
```

## ⚡ Оптимизация для продакшена

```php
// Оптимальная конфигурация
$renderer = new FileAssetRenderer(
    __DIR__ . '/public/assets',
    'https://cdn.example.com/assets/', // CDN URL
    true,                              // Минификация
    FileAssetRenderer::MODE_SINGLE,    // Один файл
    'app'                              // Префикс
);

BrickManager::setCache($psr16Cache);
BrickManager::$cacheTtl = 86400; // 24 часа
```

### Статистика и мониторинг

```php
$stats = BrickManager::getInstance()->getStats();
/*
[
    'cached_classes' => 15,     // Компонентов в кэше
    'css_assets' => 8,          // Компонентов с CSS
    'js_assets' => 5            // Компонентов с JS
]
*/
```

## Лучшие практики

1. **Разработка**: `InlineAssetRenderer` без минификации
2. **Стэйджинг**: `FileAssetRenderer` с локальными файлами
3. **Продакшн**: `FileAssetRenderer` с CDN и минификацией
4. **Мониторинг**: Регулярно проверяйте размер ассетов
5. **Очистка**: Настройте cron для удаления старых файлов

---

**Далее:** Изучите [продвинутые сценарии](advanced.md) использования библиотеки

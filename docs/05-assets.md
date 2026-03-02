# Управление ассетами

## Как это работает

Когда компонент рендерится впервые, `BrickManager` находит его директорию, читает `style.css` и `script.js` и сохраняет их в памяти. При повторном рендере того же компонента файлы не читаются — используются закэшированные данные.

В конце страницы вызывается `renderAssets()` — он собирает CSS и JS всех компонентов которые были на странице и выводит их одним блоком. Без дублей, без ручного управления зависимостями.

```php
// В конце страницы или layout-а
echo BrickManager::getInstance()->renderAssets();

// Или по отдельности
echo BrickManager::getInstance()->renderCss();
echo BrickManager::getInstance()->renderJs();
```

---

## Рендереры

`AssetRenderer` — это интерфейс с двумя методами:

```php
interface AssetRenderer
{
    public function renderCss(array $cssAssets): string;
    public function renderJs(array $jsAssets): string;
}
```

Brick поставляется с двумя реализациями. Рендерер передаётся в конструктор `BrickManager`:

```php
$manager = new BrickManager(new InlineAssetRenderer());
// или
$manager = new BrickManager(new FileAssetRenderer('/path/to/public/assets'));
```

---

## InlineAssetRenderer

Встраивает ассеты прямо в HTML через `<style>` и `<script>` теги. Подходит для разработки и небольших проектов.

```php
$renderer = new InlineAssetRenderer();
```

Результат:

```html
<style>
.btn { ... }
.card { ... }
</style>

<script>
// button.js
// card.js
</script>
```

---

## FileAssetRenderer

Записывает ассеты в файлы на диске и возвращает `<link>` и `<script src>` теги. Подходит для продакшена — браузер кэширует файлы между запросами.

```php
$renderer = new FileAssetRenderer(
    outputDir: __DIR__ . '/public/assets',
    publicUrl: '/assets/',
    minify:    true,
    mode:      FileAssetRenderer::MODE_SINGLE,
);
```

Имена файлов включают хэш содержимого — автоматический cache busting:

```html
<link rel="stylesheet" href="/assets/brick.all.a1b2c3d4.css">
<script src="/assets/brick.all.e5f6g7h8.js"></script>
```

Файл записывается на диск только если он ещё не существует. При изменении ассетов хэш меняется — создаётся новый файл.

---

## Режимы сборки

Оба рендерера поддерживают два режима:

**`MODE_SINGLE`** (по умолчанию) — все компоненты в один файл. Меньше HTTP-запросов, проще кэширование на уровне браузера.

```php
$renderer->setMode(AbstractAssetRenderer::MODE_SINGLE);
```

```html
<link rel="stylesheet" href="/assets/brick.all.a1b2c3d4.css">
```

**`MODE_MULTIPLE`** — каждый компонент в отдельный файл. Точечная инвалидация кэша: изменился `Button` — обновился только его файл.

```php
$renderer->setMode(AbstractAssetRenderer::MODE_MULTIPLE);
```

```html
<link rel="stylesheet" href="/assets/brick.button.a1b2c3d4.css">
<link rel="stylesheet" href="/assets/brick.card.e5f6g7h8.css">
```

---

## Минификация

Встроенная минификация CSS и JS без внешних зависимостей:

```php
$renderer->setMinify(true);
```

Для CSS: удаляет комментарии, лишние пробелы и переносы строк. Для JS: удаляет комментарии и лишние пробелы.

Для продакшена с высокими требованиями к размеру рекомендуется использовать полноценный сборщик через собственный рендерер.

---

## Собственный рендерер

`AssetRenderer` — интерфейс. Достаточно реализовать два метода чтобы подключить любой инструмент доставки ассетов.

### Vite

```php
class ViteAssetRenderer implements AssetRenderer
{
    private array $manifest;

    public function __construct(string $manifestPath)
    {
        $this->manifest = json_decode(
            file_get_contents($manifestPath), true
        );
    }

    public function renderCss(array $cssAssets): string
    {
        // В dev-режиме Vite сам инжектит стили через HMR
        if ($this->isDevMode()) {
            return '<script type="module" src="http://localhost:5173/@vite/client"></script>';
        }

        // В продакшене берём fingerprint из manifest.json
        $entry = $this->manifest['resources/css/app.css'] ?? null;
        if (!$entry) return '';

        return '<link rel="stylesheet" href="/build/' . $entry['file'] . '">';
    }

    public function renderJs(array $jsAssets): string
    {
        if ($this->isDevMode()) {
            return '<script type="module" src="http://localhost:5173/resources/js/app.js"></script>';
        }

        $entry = $this->manifest['resources/js/app.js'] ?? null;
        if (!$entry) return '';

        return '<script type="module" src="/build/' . $entry['file'] . '"></script>';
    }

    private function isDevMode(): bool
    {
        return ($_ENV['APP_ENV'] ?? 'production') === 'development';
    }
}

$manager = new BrickManager(new ViteAssetRenderer(__DIR__ . '/public/build/manifest.json'));
```

### S3 и CDN

```php
class S3AssetRenderer implements AssetRenderer
{
    public function __construct(
        private S3Client $s3,
        private string $bucket,
        private string $cdnUrl,
    ) {}

    public function renderCss(array $cssAssets): string
    {
        $css = implode("\n\n", $cssAssets);
        $key = 'assets/brick.' . md5($css) . '.css';

        if (!$this->s3->doesObjectExist($this->bucket, $key)) {
            $this->s3->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $key,
                'Body'        => $css,
                'ContentType' => 'text/css',
                'ACL'         => 'public-read',
            ]);
        }

        return '<link rel="stylesheet" href="' . $this->cdnUrl . $key . '">';
    }

    public function renderJs(array $jsAssets): string
    {
        $js  = implode("\n\n", $jsAssets);
        $key = 'assets/brick.' . md5($js) . '.js';

        if (!$this->s3->doesObjectExist($this->bucket, $key)) {
            $this->s3->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $key,
                'Body'        => $js,
                'ContentType' => 'application/javascript',
                'ACL'         => 'public-read',
            ]);
        }

        return '<script src="' . $this->cdnUrl . $key . '"></script>';
    }
}

$manager = new BrickManager(new S3AssetRenderer($s3, 'my-bucket', 'https://cdn.example.com/'));
```

Компоненты ничего не знают о том, куда уходят их ассеты — они просто объявляют CSS и JS рядом с собой. Всё остальное решает рендерер.

---

## Статистика

`BrickManager` умеет отдавать статистику о зарегистрированных компонентах — удобно для отладки:

```php
$stats = BrickManager::getInstance()->getStats();
// [
//   'cached_classes' => 5,
//   'css_assets'     => 4,
//   'js_assets'      => 3,
// ]

$info = BrickManager::getInstance()->getFullInfo();
// Полная информация о каждом компоненте: директория, шаблон, CSS, JS
```

---

## Что дальше

- [Тестирование и статический анализ](06-testing.md)
- [Дизайн-система и переносимость](07-design-system.md)

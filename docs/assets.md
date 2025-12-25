# Работа с ассетами

Ассеты в Brick — это не просто файлы. Это **стили и поведение**, которые живут рядом с компонентом, но могут быть доставлены разными способами в зависимости от контекста.

---

## Философия ассетов

Каждый компонент содержит:
- `style.css` — его внешний вид
- `script.js` — его поведение

Но **как** эти ассеты попадают в браузер — решаете вы.

---

## Три способа доставки ассетов

### 1. **Встроенные ассеты** (по умолчанию)
CSS и JavaScript вставляются прямо в HTML:
```html
<style>/* стили компонента */</style>
<script>/* скрипты компонента */</script>
```
**Когда использовать:** разработка, небольшие проекты, когда важна простота.

### 2. **Файловые ассеты**
Ассеты сохраняются в файлы на сервере:
```html
<link rel="stylesheet" href="/assets/brick.button.abc123.css">
<script src="/assets/brick.button.def456.js"></script>
```
**Когда использовать:** продакшен, когда нужны кэширование и разделение.

### 3. **Кастомные ассеты** (через ваш рендерер)
Ассеты могут:
- Загружаться в S3 / CDN
- Сохраняться через Flysystem
- Инлайниться в критический CSS
- Объединяться в бандлы

**Когда использовать:** специфичные требования инфраструктуры.

---

## AssetRenderer интерфейс

`AssetRenderer` — это абстракция над **способом доставки** ассетов:

```php
interface AssetRenderer
{
    public function renderCss(array $cssAssets): string;
    public function renderJs(array $jsAssets): string;
}
```

**Ваш компонент не знает**, как ассеты будут доставлены. Он только передаёт их рендереру.

---

## Готовые рендереры

### InlineAssetRenderer (по умолчанию)
```php
$renderer = new InlineAssetRenderer();
// <style>...</style>
// <script>...</script>
```

### FileAssetRenderer
```php
$renderer = new FileAssetRenderer(
    __DIR__ . '/public/assets', // куда писать
    '/assets/',                 // публичный URL
    true,                       // минифицировать
    FileAssetRenderer::MODE_SINGLE // один файл или много
);
// <link href="/assets/brick.all.abc123.css">
```

### Режимы FileAssetRenderer
- `MODE_SINGLE` — все компоненты в один файл
- `MODE_MULTIPLE` — каждый компонент в отдельный файл

---

## Написание своего рендерера

Создайте класс, реализующий `AssetRenderer`:

```php
class S3AssetRenderer implements AssetRenderer
{
    public function __construct(
        private S3Client $s3,
        private string $bucket,
        private string $cdnUrl
    ) {}

    public function renderCss(array $cssAssets): string
    {
        $css = implode("\n", $cssAssets);
        $key = 'assets/' . md5($css) . '.css';
        
        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $css
        ]);

        return sprintf('<link rel="stylesheet" href="%s/%s">', 
            $this->cdnUrl, $key);
    }
    
    public function renderJs(array $jsAssets): string { /* аналогично */ }
}
```

### Примеры кастомных рендереров:
- **FlysystemAssetRenderer** — для любого файлового хранилища
- **CriticalCssRenderer** — инлайнит критический CSS, остальное — в файлы
- **WebpackRenderer** — интегрируется с существующей сборкой
- **NoopRenderer** — для тестов, ничего не выводит

---

## Настройка рендерера

```php
use OlegV\Brick;

// Глобально для всех компонентов
Brick::setAssetRenderer(new FileAssetRenderer(...));

// Или динамически в рантайме
$renderer = new S3AssetRenderer($s3, 'my-bucket', 'https://cdn.example.com');
Brick::setAssetRenderer($renderer);
```

---

## Ассеты и наследование

При использовании трейта `WithInheritance`:
- CSS всех родителей объединяется
- JavaScript всех родителей объединяется
- Итоговые ассеты передаются в рендерер

**Рендерер не знает** об иерархии. Он получает уже подготовленные данные.

---

## Минификация

Включена в готовых рендерерах:
```php
$renderer->setMinify(true);
```

Минифицирует:
- Удаление комментариев
- Удаление лишних пробелов
- Оптимизация CSS/JS без потери функциональности

**Важно:** минификация применяется **после** объединения ассетов.

---

## Стратегии кэширования

### По хэшу контента
Имя файла включает хэш содержимого:
```
brick.button.abc123.css
```
Если ассет не изменился — имя файла то же, браузер использует кэш.

### Инвалидация кэша
При изменении компонента хэш меняется → новое имя файла → кэш сбрасывается.

---

## Рекомендации

### Для разработки:
```php
new InlineAssetRenderer() // мгновенные изменения, простота
```

### Для продакшена:
```php
new FileAssetRenderer(..., minify: true, mode: MODE_SINGLE)
```

### Для масштабирования:
```php
new S3AssetRenderer(..., cdn: 'https://global.cdn')
```

---

## Философия

Ассеты в Brick — это **ответственность компонента**, но **забота инфраструктуры**.

Компонент говорит: «Вот мои стили и поведение».  
Рендерер решает: «Как доставить их пользователю».

> Интерфейс `AssetRenderer` — это мост между логикой компонентов и реалиями доставки.  
> Он позволяет Brick работать одинаково на shared-хостинге и в облачной инфраструктуре.

---

## Что дальше?

- [Кэширование](./caching.md) — как кэшировать ассеты и HTML
- [Лучшие практики](./best-practices.md) — антипаттерны наследования

---

> Правильный рендерер ассетов — тот, который забывается после настройки.  
> Он просто работает, пока вы думаете о логике приложения.
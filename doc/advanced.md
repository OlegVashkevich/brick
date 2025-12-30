# Продвинутые сценарии

В этом разделе рассматриваются сложные сценарии использования Brick, оптимизация производительности, интеграция с другими системами и решение нестандартных задач.

## Оглавление

1. [Производительность и масштабирование](#производительность-и-масштабирование)
2. [Интеграция с фреймворками](#интеграция-с-фреймворками)
3. [Паттерны для сложных компонентов](#паттерны-для-сложных-компонентов)
4. [Тестирование компонентов](#тестирование-компонентов)
5. [Отладка и профилирование](#отладка-и-профилирование)
6. [Безопасность и аудит](#безопасность-и-аудит)

## Производительность и масштабирование

### Многоуровневое кэширование

Brick поддерживает несколько уровней кэширования для максимальной производительности:

```php
use OlegV\BrickManager;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

// 1. Цепочка кэшей (chain cache)
$fastCache = new RedisAdapter(
    RedisAdapter::createConnection('redis://localhost')
);
$slowCache = new FilesystemAdapter();

$chainCache = new ChainAdapter([$fastCache, $slowCache]);
BrickManager::setCache($chainCache);

// 2. Разные TTL для разных типов компонентов
BrickManager::$cacheTtl = 3600; // По умолчанию 1 час

// В компонентах можно переопределять TTL
readonly class NewsArticle extends Brick
{
    use \OlegV\Traits\WithCache;
    
    protected function ttl(): int
    {
        // Свежие статьи кэшируем меньше, архивные - дольше
        $age = time() - $this->publishedAt->getTimestamp();
        
        return match(true) {
            $age < 3600 => 300,      // 5 минут
            $age < 86400 => 1800,    // 30 минут
            $age < 604800 => 7200,   // 2 часа
            default => 86400         // 24 часа
        };
    }
}
```

### Оптимизация рендеринга множества компонентов

```php
// ❌ Медленно: создание и рендеринг по одному
foreach ($products as $product) {
    echo new ProductCard($product);
}

// ✅ Быстро: батчинг и кэширование
class ProductList extends Brick
{
    public function __construct(
        public array $products,
        public int $columns = 3
    ) {
        parent::__construct();
    }
    
    public function render(): string
    {
        // Кэшируем весь список
        $cacheKey = 'product_list_' . md5(serialize($this->products));
        
        return $this->cachedRender($cacheKey, function() {
            ob_start();
            foreach ($this->products as $product) {
                echo new ProductCard($product);
            }
            return ob_get_clean();
        });
    }
    
    private function cachedRender(string $key, callable $renderer): string
    {
        $cache = BrickManager::getCache();
        if ($cache && $cached = $cache->get($key)) {
            return $cached;
        }
        
        $html = $renderer();
        $cache?->set($key, $html, 3600);
        
        return $html;
    }
}
```

### Оптимизация ассетов для больших приложений

```php
use OlegV\Assets\FileAssetRenderer;
use OlegV\BrickManager;

// Стратегия "разделения по чанкам"
$renderer = new FileAssetRenderer(
    __DIR__ . '/public/assets',
    '/assets/',
    true,
    FileAssetRenderer::MODE_MULTIPLE
);

// Группируем компоненты по функциональности
$componentGroups = [
    'layout' => ['Header', 'Footer', 'Sidebar'],
    'forms' => ['Input', 'Button', 'Select', 'Checkbox'],
    'ui' => ['Modal', 'Dropdown', 'Tooltip', 'Alert'],
];

// Динамически подключаем только нужные группы
class AssetManager
{
    private array $usedGroups = [];
    
    public function useGroup(string $group): void
    {
        $this->usedGroups[$group] = true;
    }
    
    public function renderCss(): string
    {
        $manager = BrickManager::getInstance();
        $allAssets = $manager->getFullInfo();
        
        // Фильтруем только компоненты из используемых групп
        $filteredAssets = [];
        foreach ($allAssets as $className => $data) {
            $shortName = basename(str_replace('\\', '/', $className));
            
            foreach ($this->usedGroups as $group => $_) {
                if (in_array($shortName, $GLOBALS['componentGroups'][$group] ?? [])) {
                    $filteredAssets[$className] = $data['css'];
                    break;
                }
            }
        }
        
        return $manager->getAssetRenderer()->renderCss($filteredAssets);
    }
}

// Использование
$assetManager = new AssetManager();
$assetManager->useGroup('layout');
$assetManager->useGroup('forms');
echo $assetManager->renderCss();
```

## Интеграция с фреймворками

### Laravel

```php
// App/Providers/BrickServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OlegV\BrickManager;
use OlegV\Assets\FileAssetRenderer;
use Illuminate\Contracts\Cache\Repository;

class BrickServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Конфигурация из config/brick.php
        $this->mergeConfigFrom(__DIR__.'/../config/brick.php', 'brick');
    }
    
    public function boot(): void
    {
        // Публикация конфигурации
        $this->publishes([
            __DIR__.'/../config/brick.php' => config_path('brick.php'),
        ], 'brick-config');
        
        // Настройка Brick
        $this->configureBrick();
        
        // Blade директивы
        $this->registerBladeDirectives();
    }
    
    private function configureBrick(): void
    {
        // Кэширование через Laravel Cache
        BrickManager::setCache(
            new LaravelCacheAdapter(app(Repository::class))
        );
        
        // Рендерер ассетов
        $renderer = new FileAssetRenderer(
            public_path('assets/brick'),
            asset('assets/brick') . 'advanced.md/',
            config('app.env') === 'production',
            FileAssetRenderer::MODE_SINGLE
        );
        
        BrickManager::getInstance()->setAssetRenderer($renderer);
        
        // Автоматическая регистрация компонентов
        $this->autoDiscoverComponents();
    }
    
    private function registerBladeDirectives(): void
    {
        \Blade::directive('brickAssets', function () {
            return "<?php echo \OlegV\BrickManager::getInstance()->renderAssets(); ?>";
        });
        
        \Blade::directive('brickCss', function () {
            return "<?php echo \OlegV\BrickManager::getInstance()->renderCss(); ?>";
        });
        
        \Blade::directive('brickJs', function () {
            return "<?php echo \OlegV\BrickManager::getInstance()->renderJs(); ?>";
        });
    }
    
    private function autoDiscoverComponents(): void
    {
        // Автоматическое обнаружение компонентов в app/View/Components
        $path = app_path('View/Components');
        
        if (file_exists($path)) {
            foreach (glob($path . '/*.php') as $file) {
                require_once $file;
            }
        }
    }
}

// Адаптер Laravel Cache для PSR-16
class LaravelCacheAdapter implements \Psr\SimpleCache\CacheInterface
{
    public function __construct(
        private Repository $cache
    ) {}
    
    public function get($key, $default = null): mixed
    {
        $value = $this->cache->get($key);
        return $value === null ? $default : $value;
    }
    
    public function set($key, $value, $ttl = null): bool
    {
        return $this->cache->put($key, $value, $ttl);
    }
    
    // ... остальные методы интерфейса
}

// Использование в Blade
{{-- layout.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    @brickCss()
</head>
<body>
    @yield('content')
    @brickJs()
</body>
</html>

{{-- page.blade.php --}}
@extends('layout')

@section('content')
    @foreach($products as $product)
        {{ new \App\View\Components\ProductCard($product) }}
    @endforeach
@endsection
```

### Symfony

```php
// config/packages/brick.yaml
brick:
  cache:
    service: 'cache.app'  # PSR-6 или PSR-16 сервис
    ttl: 3600
    prefix: 'brick_'
  assets:
    renderer: 'file'      # file, inline, или service id
    output_dir: '%kernel.project_dir%/public/assets'
    public_url: '/assets/'
    minify: '%kernel.debug%'
    mode: 'single'

// src/Brick/BrickBundle.php
namespace App\Brick;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BrickBundle extends Bundle
{
    public function boot(): void
    {
        $container = $this->container;
        
        // Настройка из конфига
        $config = $container->getParameter('brick');
        
        // Кэширование
        if ($config['cache']['service']) {
            BrickManager::setCache(
                $container->get($config['cache']['service'])
            );
        }
        
        BrickManager::$cacheTtl = $config['cache']['ttl'];
        BrickManager::$cachePrefix = $config['cache']['prefix'];
        
        // Рендерер ассетов
        $renderer = match($config['assets']['renderer']) {
            'file' => new FileAssetRenderer(
                $config['assets']['output_dir'],
                $config['assets']['public_url'],
                $config['assets']['minify'],
                $config['assets']['mode']
            ),
            'inline' => new InlineAssetRenderer(),
            default => $container->get($config['assets']['renderer'])
        };
        
        BrickManager::getInstance()->setAssetRenderer($renderer);
    }
}

// Twig расширение
namespace App\Brick\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrickExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('brick_assets', [BrickRuntime::class, 'renderAssets']),
            new TwigFunction('brick_css', [BrickRuntime::class, 'renderCss']),
            new TwigFunction('brick_js', [BrickRuntime::class, 'renderJs']),
        ];
    }
}

// Использование в Twig
{# base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    {{ brick_css() }}
</head>
<body>
    {% block content %}{% endblock %}
    {{ brick_js() }}
</body>
</html>
```

## Паттерны для сложных компонентов

### Компоненты с порталами (Portals)

```php
// Порталы для рендеринга в разных местах DOM
readonly class Modal extends Brick
{
    use \OlegV\Traits\WithInheritance;
    
    public function __construct(
        public string $title,
        public string $content,
        public bool $open = false
    ) {
        parent::__construct();
    }
    
    public function renderPortal(): string
    {
        // Рендерим в body, а не на месте вызова
        if ($this->open) {
            return $this->render();
        }
        return '';
    }
}

// В приложении
$modal = new Modal('Заголовок', 'Содержимое модалки', true);

// Основной контент
echo new ProductList($products);

// Порталы рендерятся в конце body
echo '<div id="portals">';
echo $modal->renderPortal();
echo '</div>';

// script.js для Modal
document.addEventListener('DOMContentLoaded', function() {
    // Перемещаем порталы в body
    const portals = document.getElementById('portals');
    if (portals) {
        document.body.appendChild(portals);
        portals.id = '';
    }
});
```

### Высокоуровневые компоненты (Higher-Order Components)

```php
// HOC для добавления функциональности
trait WithLoading
{
    private bool $isLoading = false;
    
    public function startLoading(): void
    {
        $this->isLoading = true;
    }
    
    public function stopLoading(): void
    {
        $this->isLoading = false;
    }
    
    public function isLoading(): bool
    {
        return $this->isLoading;
    }
    
    protected function renderWithLoading(callable $renderer): string
    {
        if ($this->isLoading()) {
            return '<div class="loading">Загрузка...</div>';
        }
        
        return $renderer();
    }
}

// Использование HOC
readonly class DataTable extends Brick
{
    use WithLoading;
    
    public function __construct(
        private \DataProvider $provider,
        public array $columns
    ) {
        parent::__construct();
        $this->startLoading();
    }
    
    public function render(): string
    {
        return $this->renderWithLoading(function() {
            $data = $this->provider->fetchData();
            
            ob_start();
            include $this->getTemplatePath();
            return ob_get_clean();
        });
    }
    
    public function loadData(): void
    {
        $this->startLoading();
        // Асинхронная загрузка данных...
        $this->stopLoading();
    }
}
```

### Компоненты с контекстом (Context)

```php
// Контекст для передачи данных через дерево компонентов
class ThemeContext
{
    private static array $context = [];
    
    public static function set(string $key, mixed $value): void
    {
        self::$context[$key] = $value;
    }
    
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$context[$key] ?? $default;
    }
}

// Компонент, использующий контекст
readonly class ThemedButton extends Brick
{
    public function __construct(
        public string $text
    ) {
        parent::__construct();
    }
    
    public function getTheme(): string
    {
        return ThemeContext::get('theme', 'light');
    }
    
    public function isDarkMode(): bool
    {
        return $this->getTheme() === 'dark';
    }
}

// template.php
<button class="btn btn-<?= $this->isDarkMode() ? 'dark' : 'light' ?>">
    <?= $this->e($this->text) ?>
</button>

// Установка контекста
ThemeContext::set('theme', 'dark');
echo new ThemedButton('Кнопка в тёмной теме');
```

## Тестирование компонентов

### Юнит-тесты компонентов

```php
// tests/Unit/ButtonTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YourApp\UI\Button;

class ButtonTest extends TestCase
{
    public function test_button_renders_correctly(): void
    {
        $button = new Button('Click me', 'primary');
        
        $html = (string)$button;
        
        $this->assertStringContainsString('Click me', $html);
        $this->assertStringContainsString('btn-primary', $html);
        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('</button>', $html);
    }
    
    public function test_button_escapes_html(): void
    {
        $button = new Button('<script>alert("xss")</script>');
        
        $html = (string)$button;
        
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
    
    public function test_button_variants(): void
    {
        $primary = new Button('Primary', 'primary');
        $secondary = new Button('Secondary', 'secondary');
        $success = new Button('Success', 'success');
        
        $this->assertStringContainsString('btn-primary', (string)$primary);
        $this->assertStringContainsString('btn-secondary', (string)$secondary);
        $this->assertStringContainsString('btn-success', (string)$success);
    }
    
    public function test_button_disabled_state(): void
    {
        $disabled = new Button('Disabled', 'primary', true);
        
        $html = (string)$disabled;
        
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('btn-disabled', $html);
    }
}
```

### Интеграционные тесты

```php
// tests/Integration/ComponentIntegrationTest.php
namespace Tests\Integration;

use OlegV\BrickManager;
use PHPUnit\Framework\TestCase;

class ComponentIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BrickManager::getInstance()->clear();
    }
    
    public function test_component_registration_and_rendering(): void
    {
        $button = new Button('Test');
        
        // Проверяем регистрацию в BrickManager
        $this->assertTrue(
            BrickManager::getInstance()->isComponentMemoized(Button::class)
        );
        
        // Проверяем рендеринг
        $html = (string)$button;
        $this->assertNotEmpty($html);
        
        // Проверяем ассеты
        $css = BrickManager::getInstance()->renderCss();
        $this->assertStringContainsString('.btn', $css);
    }
    
    public function test_cache_integration(): void
    {
        $cache = new ArrayCache();
        BrickManager::setCache($cache);
        
        $button = new Button('Cached');
        
        // Первый рендер должен сохранить в кэш
        $html1 = (string)$button;
        
        // Второй рендер должен взять из кэша
        $html2 = (string)$button;
        
        $this->assertEquals($html1, $html2);
        
        // Проверяем, что кэш был использован
        $this->assertGreaterThan(0, count($cache->getValues()));
    }
}

// Тестовый кэш для тестов
class ArrayCache implements \Psr\SimpleCache\CacheInterface
{
    private array $values = [];
    
    public function get($key, $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }
    
    public function set($key, $value, $ttl = null): bool
    {
        $this->values[$key] = $value;
        return true;
    }
    
    public function getValues(): array
    {
        return $this->values;
    }
    
    // ... остальные методы интерфейса
}
```

### Тестирование производительности

```php
// tests/Performance/ComponentPerformanceTest.php
namespace Tests\Performance;

use PHPUnit\Framework\TestCase;

class ComponentPerformanceTest extends TestCase
{
    private const ITERATIONS = 1000;
    
    public function test_rendering_performance(): void
    {
        $start = microtime(true);
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $button = new Button("Button {$i}", 'primary');
            $html = (string)$button;
        }
        
        $time = microtime(true) - $start;
        $average = $time / self::ITERATIONS * 1000; // мс на компонент
        
        $this->assertLessThan(
            5.0, // Максимум 5ms на компонент
            $average,
            "Average render time: {$average}ms (max: 5ms)"
        );
    }
    
    public function test_memory_usage(): void
    {
        $startMemory = memory_get_usage();
        $components = [];
        
        for ($i = 0; $i < 100; $i++) {
            $components[] = new Button("Button {$i}");
        }
        
        $memoryUsed = memory_get_usage() - $startMemory;
        $perComponent = $memoryUsed / 100;
        
        $this->assertLessThan(
            10240, // 10KB на компонент
            $perComponent,
            "Memory per component: {$perComponent} bytes (max: 10KB)"
        );
        
        // Очистка
        unset($components);
        gc_collect_cycles();
    }
}
```

## Отладка и профилирование

### Отладка компонентов

```php
// Трейт для отладки
trait WithDebug
{
    private array $debugLog = [];
    
    protected function logDebug(string $message, array $context = []): void
    {
        $this->debugLog[] = [
            'time' => microtime(true),
            'message' => $message,
            'context' => $context,
            'class' => static::class
        ];
    }
    
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }
    
    public function renderDebugInfo(): string
    {
        if ($_ENV['APP_DEBUG'] !== 'true') {
            return '';
        }
        
        $html = '<div class="brick-debug">';
        $html .= '<h3>Debug: ' . static::class . '</h3>';
        $html .= '<pre>' . htmlspecialchars(
            print_r($this->debugLog, true)
        ) . '</pre>';
        $html .= '</div>';
        
        return $html;
    }
}

// Компонент с отладкой
readonly class DebuggableComponent extends Brick
{
    use WithDebug;
    
    public function __construct(public string $data)
    {
        parent::__construct();
        $this->logDebug('Component constructed', ['data' => $data]);
    }
    
    public function render(): string
    {
        $this->logDebug('Rendering started');
        $result = parent::render();
        $this->logDebug('Rendering completed', [
            'html_length' => strlen($result)
        ]);
        
        return $result . $this->renderDebugInfo();
    }
}
```

### Профилирование производительности

```php
class BrickProfiler
{
    private static array $profileData = [];
    private static float $startTime = 0;
    
    public static function start(string $component): void
    {
        self::$startTime = microtime(true);
        self::$profileData[$component] = [
            'start' => self::$startTime,
            'renders' => 0,
            'total_time' => 0
        ];
    }
    
    public static function end(string $component): void
    {
        if (!isset(self::$profileData[$component])) {
            return;
        }
        
        $time = microtime(true) - self::$startTime;
        self::$profileData[$component]['renders']++;
        self::$profileData[$component]['total_time'] += $time;
    }
    
    public static function getReport(): array
    {
        $report = [];
        
        foreach (self::$profileData as $component => $data) {
            if ($data['renders'] > 0) {
                $report[$component] = [
                    'renders' => $data['renders'],
                    'total_time' => $data['total_time'],
                    'average_time' => $data['total_time'] / $data['renders']
                ];
            }
        }
        
        uasort($report, fn($a, $b) => $b['total_time'] <=> $a['total_time']);
        
        return $report;
    }
}

// Обёртка для профилирования
function profile_component(string $component, callable $render): string
{
    BrickProfiler::start($component);
    $result = $render();
    BrickProfiler::end($component);
    
    return $result;
}

// Использование
$html = profile_component(Button::class, function() {
    return (string)new Button('Click me');
});
```

### Инструменты разработчика

```php
// DevTools панель для браузера
readonly class BrickDevTools extends Brick
{
    public function render(): string
    {
        if ($_ENV['APP_ENV'] !== 'development') {
            return '';
        }
        
        $stats = BrickManager::getInstance()->getStats();
        $profile = BrickProfiler::getReport();
        
        ob_start(); ?>
        <div id="brick-devtools">
            <style>
            #brick-devtools {
                position: fixed;
                bottom: 0;
                right: 0;
                background: #1a1a1a;
                color: white;
                padding: 10px;
                font-family: monospace;
                font-size: 12px;
                border-top-left-radius: 5px;
                z-index: 9999;
                max-width: 400px;
                max-height: 300px;
                overflow: auto;
            }
            .brick-stats {
                margin-bottom: 10px;
            }
            .brick-stat {
                display: flex;
                justify-content: space-between;
                margin-bottom: 3px;
            }
            </style>
            
            <div class="brick-stats">
                <div class="brick-stat">
                    <span>Components:</span>
                    <span><?= $stats['cached_classes'] ?></span>
                </div>
                <div class="brick-stat">
                    <span>CSS Assets:</span>
                    <span><?= $stats['css_assets'] ?></span>
                </div>
                <div class="brick-stat">
                    <span>JS Assets:</span>
                    <span><?= $stats['js_assets'] ?></span>
                </div>
            </div>
            
            <?php if (!empty($profile)): ?>
            <div class="brick-profile">
                <strong>Performance Profile:</strong>
                <?php foreach ($profile as $component => $data): ?>
                <div class="brick-stat">
                    <span><?= $component ?>:</span>
                    <span><?= number_format($data['average_time'] * 1000, 2) ?>ms</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const devtools = document.getElementById('brick-devtools');
            if (devtools) {
                // Перемещаем в body если нужно
                if (devtools.parentNode !== document.body) {
                    document.body.appendChild(devtools);
                }
                
                // Добавляем переключатель видимости
                const toggle = document.createElement('button');
                toggle.textContent = 'Brick';
                toggle.style.cssText = `
                    position: fixed;
                    bottom: 10px;
                    right: 10px;
                    background: #1a1a1a;
                    color: white;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 3px;
                    cursor: pointer;
                    z-index: 10000;
                `;
                toggle.addEventListener('click', function() {
                    devtools.style.display = 
                        devtools.style.display === 'none' ? 'block' : 'none';
                });
                document.body.appendChild(toggle);
            }
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
}

// Добавление в приложение
echo new BrickDevTools();
```

## Безопасность и аудит

### Content Security Policy (CSP)

```php
// Генератор CSP nonce для скриптов
class CspNonceGenerator
{
    private static ?string $nonce = null;
    
    public static function getNonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = bin2hex(random_bytes(16));
        }
        return self::$nonce;
    }
}

// AssetRenderer с поддержкой CSP
class CspAssetRenderer extends \OlegV\Assets\InlineAssetRenderer
{
    public function renderJs(array $jsAssets): string
    {
        if (empty($jsAssets)) {
            return '';
        }
        
        $processed = $this->processJsAssets($jsAssets);
        $nonce = CspNonceGenerator::getNonce();
        $scripts = [];
        
        foreach ($processed as $js) {
            $scripts[] = sprintf(
                '<script nonce="%s">%s</script>',
                htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8'),
                $js
            );
        }
        
        return implode("\n", $scripts);
    }
}

// Установка CSP заголовка
header(sprintf(
    "Content-Security-Policy: script-src 'nonce-%s'",
    CspNonceGenerator::getNonce()
));
```

### Валидация входных данных

```php
trait WithInputValidation
{
    protected function validateInput(array $rules): void
    {
        foreach ($rules as $property => $rule) {
            $value = $this->$property ?? null;
            
            if (!$this->validateRule($value, $rule)) {
                throw new \InvalidArgumentException(
                    sprintf('Validation failed for property %s', $property)
                );
            }
        }
    }
    
    private function validateRule(mixed $value, mixed $rule): bool
    {
        if (is_callable($rule)) {
            return $rule($value);
        }
        
        if (is_string($rule)) {
            return match($rule) {
                'required' => $value !== null && $value !== '',
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL),
                'url' => filter_var($value, FILTER_VALIDATE_URL),
                'int' => is_int($value),
                'float' => is_float($value),
                'array' => is_array($value),
                'bool' => is_bool($value),
                default => true
            };
        }
        
        return true;
    }
}

// Использование
readonly class UserProfile extends Brick
{
    use WithInputValidation;
    
    public function __construct(
        public string $email,
        public string $username,
        public int $age,
        public ?string $avatarUrl = null
    ) {
        $this->validateInput([
            'email' => 'email',
            'username' => fn($v) => strlen($v) >= 3 && strlen($v) <= 50,
            'age' => fn($v) => $v >= 0 && $v <= 150,
            'avatarUrl' => 'url'
        ]);
        
        parent::__construct();
    }
}
```

---

Это руководство покрывает продвинутые темы от оптимизации производительности до интеграции с фреймворками и решения сложных задач. Каждый раздел содержит практические примеры и готовые к использованию паттерны.

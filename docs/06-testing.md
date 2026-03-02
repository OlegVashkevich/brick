# Тестирование и статический анализ

Одно из главных преимуществ Brick — компоненты это обычные PHP-классы. Всё что умеет делать PHP-экосистема с классами, работает с компонентами без дополнительной настройки.

---

## Unit-тесты

### Базовый тест

```php
use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{
    public function test_renders_text(): void
    {
        $html = (new Button(text: 'Купить'))->render();

        $this->assertStringContainsString('Купить', $html);
    }

    public function test_renders_variant_class(): void
    {
        $html = (new Button(text: 'Купить', variant: 'danger'))->render();

        $this->assertStringContainsString('btn--danger', $html);
    }

    public function test_escapes_html(): void
    {
        $html = (new Button(text: '<script>alert(1)</script>'))->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
```

Никаких моков шаблонизатора, никакой настройки окружения. Создали объект, получили строку, проверили содержимое.

### Тест с вложенными компонентами

```php
class CardTest extends TestCase
{
    public function test_renders_nested_button(): void
    {
        $card = new Card(
            title: 'Заголовок',
            body: 'Текст',
            action: new Button(text: 'Подробнее'),
        );

        $html = $card->render();

        $this->assertStringContainsString('Заголовок', $html);
        $this->assertStringContainsString('Подробнее', $html);
    }

    public function test_renders_without_optional_badge(): void
    {
        $html = (new Card(
            title: 'Заголовок',
            body: 'Текст',
            action: new Button(text: 'OK'),
        ))->render();

        $this->assertStringNotContainsString('badge', $html);
    }
}
```

### Тест prepare()

```php
class PriceTagTest extends TestCase
{
    public function test_formats_price(): void
    {
        $html = (new PriceTag(price: 1234567))->render();

        $this->assertStringContainsString('1 234 567', $html);
        $this->assertStringContainsString('₽', $html);
    }

    public function test_shows_discount_when_old_price_provided(): void
    {
        $html = (new PriceTag(price: 800, oldPrice: 1000))->render();

        $this->assertStringContainsString('-20%', $html);
    }

    public function test_no_discount_when_price_is_higher(): void
    {
        $html = (new PriceTag(price: 1000, oldPrice: 800))->render();

        $this->assertStringNotContainsString('%', $html);
    }
}
```

### Тест ассетов

```php
class ComponentAssetsTest extends TestCase
{
    protected function setUp(): void
    {
        // Сбрасываем состояние менеджера перед каждым тестом
        BrickManager::getInstance()->clear();
    }

    public function test_registers_css_after_render(): void
    {
        (new Button(text: 'Test'))->render();

        $stats = BrickManager::getInstance()->getStats();
        $this->assertGreaterThan(0, $stats['css_assets']);
    }

    public function test_renders_assets_once_for_multiple_instances(): void
    {
        (new Button(text: 'First'))->render();
        (new Button(text: 'Second'))->render();
        (new Button(text: 'Third'))->render();

        $css = BrickManager::getInstance()->renderCss();

        // CSS одного компонента не дублируется
        $this->assertEquals(1, substr_count($css, '.btn'));
    }
}
```

### Тест кэширования

```php
class WithCacheTest extends TestCase
{
    public function test_returns_cached_result(): void
    {
        $cache = new ArrayCachePool(); // PSR-16 in-memory кэш
        BrickManager::setCache($cache);

        $component = new ProductCard(title: 'Товар', price: 990);

        $first  = $component->render();
        $second = $component->render();

        $this->assertSame($first, $second);
    }

    public function test_different_props_different_cache_keys(): void
    {
        $cache = new ArrayCachePool();
        BrickManager::setCache($cache);

        $html1 = (new ProductCard(title: 'Товар А', price: 100))->render();
        $html2 = (new ProductCard(title: 'Товар Б', price: 200))->render();

        $this->assertNotSame($html1, $html2);
    }
}
```

---

## Иммутабельность упрощает тесты

Поскольку `Brick` это `readonly`, тесты не зависят от порядка запуска и не влияют друг на друга. Не нужно думать о сбросе состояния между тестами — его просто нет.

```php
// Эти тесты можно запускать в любом порядке — результат всегда одинаковый
public function test_a(): void
{
    $this->assertStringContainsString('foo', (new MyComponent(value: 'foo'))->render());
}

public function test_b(): void
{
    $this->assertStringContainsString('bar', (new MyComponent(value: 'bar'))->render());
}
```

---

## PHPStan

Brick рассчитан на работу с PHPStan на максимальном уровне строгости. Поскольку `template.php` — это обычный PHP-файл, PHPStan анализирует его как любой другой код.

### Настройка

```neon
# phpstan.neon
parameters:
    level: max
    paths:
        - src
    scanFiles:
        - src/Components/Button/template.php
        - src/Components/Card/template.php
```

Либо сканировать все шаблоны сразу:

```neon
parameters:
    level: max
    paths:
        - src
    scanDirectories:
        - src/Components
```

### Аннотация @var обязательна

Чтобы PHPStan понимал типы внутри шаблона, аннотация `@var` обязательна:

```php
// template.php
/** @var Button $this */
?>
<button class="btn btn--<?= $this->e($this->variant) ?>">
```

Без неё PHPStan не знает что такое `$this` в контексте шаблона и не сможет проверить типы свойств.

### Что проверяет PHPStan

- Обращение к несуществующим свойствам компонента
- Передачу значений неправильного типа в конструктор
- Вызов несуществующих методов в шаблоне
- Nullable-значения без проверки на null

```php
// PHPStan поймает эти ошибки до запуска кода

echo new Button(text: 'OK', variant: 123);
// ❌ Argument #2 ($variant) expects string, int given

// template.php без проверки nullable:
/** @var Card $this */
echo $this->imageUrl->width; // ❌ Cannot access property on ?string
```

---

## Структура тестов

Рекомендуемая структура — тесты зеркалят структуру компонентов:

```
src/
  Components/
    Button/
      Button.php
      template.php
      style.css
tests/
  Components/
    Button/
      ButtonTest.php
    Card/
      CardTest.php
```

---

## Что дальше

- [Дизайн-система и переносимость](07-design-system.md)
- [Интеграции и сравнение с аналогами](08-integrations.md)

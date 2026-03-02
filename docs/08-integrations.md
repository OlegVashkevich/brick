# Интеграции и сравнение с аналогами

## Twig

Brick-компоненты работают в Twig без какой-либо настройки — `__toString()` вызывается автоматически при выводе объекта.

### Передача объекта из контроллера

Самый простой способ — подготовить компонент в контроллере и передать в шаблон:

```php
// Controller
class ProductController
{
    public function show(int $id): string
    {
        $product = $this->repository->find($id);

        return $this->twig->render('product/show.html.twig', [
            'card' => new ProductCard(
                title: $product->title,
                price: $product->price,
                action: new Button(text: 'В корзину'),
            ),
        ]);
    }
}
```

```twig
{# product/show.html.twig #}
<main>
    {{ card }}
</main>
```

### Twig-функция для удобства

Если хочется создавать компоненты прямо в шаблоне — зарегистрируйте функцию:

```php
$twig->addFunction(new TwigFunction('brick', function (string $class, array $props = []) {
    return new $class(...$props);
}));
```

```twig
{{ brick('App\\Components\\Button\\Button', { text: 'Нажми меня', variant: 'primary' }) }}
```

### Ассеты в layout-е

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    {% block head %}{% endblock %}
</head>
<body>
    {% block content %}{% endblock %}
</body>

{# Выводим после body — все компоненты уже отрендерились #}
{{ brickAssets()|raw }}

</html>
```

```php
$twig->addFunction(new TwigFunction('brickAssets', function () {
    return BrickManager::getInstance()->renderAssets();
}));
```

---

## Blade

В Laravel компонент передаётся в шаблон через контроллер или напрямую в `view()`:

```php
// Controller
public function index()
{
    return view('products.index', [
        'card' => new ProductCard(
            title: 'Товар',
            price: 990,
        ),
    ]);
}
```

```blade
{{-- products/index.blade.php --}}
<main>
    {{ $card }}
</main>
```

### Ассеты через @stack

```blade
{{-- layouts/app.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
```

```blade
{{-- в нужном шаблоне #}}
@push('styles')
    {!! app(\OlegV\BrickManager::class)->renderCss() !!}
@endpush

@push('scripts')
    {!! app(\OlegV\BrickManager::class)->renderJs() !!}
@endpush
```

### Сервис-провайдер

Для удобства зарегистрируйте `BrickManager` в контейнере Laravel:

```php
class BrickServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BrickManager::class, function () {
            $manager = BrickManager::getInstance();

            if (config('cache.default') !== 'array') {
                $manager->setCache(
                    new LaravelCacheAdapter(app('cache.store'))
                );
            }

            return $manager;
        });
    }
}
```

---

## Постепенная миграция

Brick не требует переписывать проект целиком. Можно начать с одного компонента — самого часто повторяющегося или самого сложного — и мигрировать постепенно.

Типичный сценарий:

1. Выбрать один компонент — например кнопку или карточку товара
2. Создать класс и шаблон, перенести стили
3. Заменить все вхождения в Twig/Blade на передачу объекта
4. Убедиться что всё работает, добавить тесты
5. Повторить для следующего компонента

Старые Twig/Blade-шаблоны продолжают работать как прежде. Новые компоненты на Brick появляются рядом. Никакого большого взрыва.

---

## Сравнение с аналогами

### Symfony UX Components

Symfony UX Components — ближайший аналог в мире Symfony. Класс + Twig-шаблон, похожая идея.

Но есть принципиальные отличия. Компонент обязан быть зарегистрирован через атрибут `#[AsTwigComponent]` и DI-контейнер — он не существует без фреймворка. Данные в шаблон передаются через `expose()` или публичные свойства, но шаблон остаётся Twig-ом — ассоциативные массивы, никакой строгой типизации внутри. CSS и JS живут отдельно в привычном для Symfony месте.

```php
// Symfony UX
#[AsTwigComponent]
class Button
{
    public string $text = '';
    public string $variant = 'primary';
}
```

```twig
{# templates/components/Button.html.twig #}
<button class="btn btn--{{ variant }}">{{ text }}</button>
```

Работает хорошо внутри Symfony, но привязан к нему намертво.

### Laravel Blade Components

Laravel Blade Components по духу ближе всего к Brick — класс и шаблон рядом, `$this` в шаблоне.

Отличия: компоненты работают только в Laravel, шаблоны на Blade DSL, CSS и JS живут в `resources/` а не рядом с компонентом. Строгая типизация в шаблоне ограничена возможностями Blade. PHPStan работает хуже — Blade компилируется в PHP, и не весь код анализируется напрямую.

```php
// Laravel Blade Component
class Button extends Component
{
    public function __construct(
        public string $text,
        public string $variant = 'primary',
    ) {}

    public function render(): View
    {
        return view('components.button');
    }
}
```

```blade
{{-- resources/views/components/button.blade.php --}}
<button class="btn btn--{{ $variant }}">{{ $text }}</button>
```

### Итоговое сравнение

|                          | Brick         | Symfony UX    | Laravel Blade |
|--------------------------|---------------|---------------|---------------|
| Фреймворк                | Независим     | Только Symfony | Только Laravel |
| Типизация в шаблоне      | Полная        | Нет           | Частичная     |
| PHPStan в шаблонах       | Да            | Нет           | Частично      |
| CSS/JS рядом с компонентом | Да          | Нет           | Нет           |
| Переносимость            | Полная        | Нет           | Нет           |
| Иммутабельность          | По умолчанию  | Нет           | Нет           |
| Unit-тесты без окружения | Да            | Нет           | Нет           |

Ни один из вариантов не является универсально лучшим. Если проект уже на Symfony или Laravel и нет задачи переносить компоненты между проектами — встроенные решения вполне достаточны. Brick имеет смысл когда важна независимость от фреймворка, строгая типизация в шаблонах, полноценное тестирование UI-слоя или долгосрочная дизайн-система.

---

## Что дальше

Вы прочитали всю документацию. Если что-то осталось непонятным или вы нашли ошибку — открывайте issue на GitHub.

- [Вернуться к README](../README.md)
- [Философия и архитектура](01-philosophy.md)

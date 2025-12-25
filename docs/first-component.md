# Создание первого компонента

Компонент в Brick — это не просто класс. Это **самодостаточная единица интерфейса**, объединяющая логику, разметку, стили и поведение в одном месте.

---

## Анатомия компонента

Каждый компонент состоит из 4 файлов в одной директории:

```
Button/
├── Button.php      # Класс с логикой и свойствами
├── template.php    # HTML-разметка
├── style.css       # Стили (опционально)
└── script.js       # JavaScript (опционально)
```

---

## Шаг за шагом

### 1. Создайте директорию
```
src/Components/Button/
```

### 2. Класс компонента (`Button.php`)

```php
<?php

namespace App\Components;

use OlegV\Brick;

class Button extends Brick
{
    public function __construct(
        public string $text = 'Click me',
        public string $variant = 'primary',
        public bool $disabled = false
    ) {}
}
```

Класс описывает:
- **Что** компонент делает (логика)
- **Какие данные** принимает (свойства)
- **Как** он инициализируется (конструктор)

### 3. Шаблон (`template.php`)

```php
<button 
    class="btn btn-<?= $this->e($this->variant) ?>"
    <?= $this->disabled ? 'disabled' : '' ?>
>
    <?= $this->e($this->text) ?>
</button>
```

Шаблон — это **представление**. Здесь:
- Используются свойства из класса
- Применяется экранирование (`$this->e()`)
- Логика минимальна и декларативна

### 4. Стили (`style.css`)

```css
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
```

Стили живут **рядом** с компонентом. Они:
- Локальны по умолчанию
- Не конфликтуют с другими компонентами
- Легко находимы и изменяемы

### 5. JavaScript (`script.js`)

```javascript
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn')) {
        console.log('Button clicked:', e.target.textContent);
    }
});
```

Скрипты — это **поведение**. Они:
- Обрабатывают события этого компонента
- Не затрагивают другие элементы
- Могут быть встроены или вынесены в файлы

---

## Использование

```php
use App\Components\Button;

// Простой вызов
echo new Button('Submit', 'primary');

// Свойства как именованные параметры
$button = new Button(
    text: 'Delete',
    variant: 'danger',
    disabled: true
);

echo $button;
```

---

## Философия компонента

### 1. **Инкапсуляция**
Компонент знает о себе всё. Внешнему миру не нужно знать, как он устроен внутри.

### 2. **Самодостаточность**
Если вы скопируете директорию `Button/` в другой проект — он будет работать.

### 3. **Ясность**
Четыре файла — четыре ответственности. Ничего лишнего.

### 4. **Тестируемость**
Компонент можно тестировать изолированно: логику, вывод, стили, поведение.

---

## Когда компонент готов?

Компонент считается завершённым, когда:

1. **Принимает все необходимые данные** через конструктор
2. **Рендерит корректный HTML** в любой ситуации
3. **Стилизует себя** без влияния на окружение
4. **Поведение** описано (если нужно)
5. **Может быть использован** без знания о его внутреннем устройстве

---

## Пример полного компонента

```
Alert/
├── Alert.php
├── template.php
├── style.css
└── script.js
```

**Alert.php:**
```php
class Alert extends Brick
{
    public function __construct(
        public string $message,
        public string $type = 'info'
    ) {}
}
```

**template.php:**
```php
<div class="alert alert-<?= $this->e($this->type) ?>">
    <?= $this->e($this->message) ?>
</div>
```

**style.css:**
```css
.alert {
    padding: 16px;
    border-radius: 4px;
    margin: 12px 0;
}

.alert-info { background: #e3f2fd; }
.alert-warning { background: #fff3cd; }
```

**script.js:**
```javascript
// Может быть пустым
```

---

## Что дальше?

- [Наследование и композиция](./inheritance.md) — как строить сложные компоненты из простых
- [Работа с ассетами](./assets.md) — управление стилями и скриптами
- [Кэширование](./caching.md) — оптимизация производительности

---

> Компонент — это не просто кусок кода.  
> Это обещание: «Я решаю одну задачу, и делаю это хорошо».
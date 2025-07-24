# UFO Event Sourcing

Легка та сучасна бібліотека Event Sourcing для PHP 8.3+

---

## 🚀 Основні можливості

* Збереження доменних подій замість поточного стану
* Відновлення агрегатів через відтворення подій (replay)
* Підтримка проєкцій для побудови read-моделей
* Інтерфейси для кастомних event store та диспетчерів

---

## 📖 Основні концепції

* **Resolver** — модуль для генерації змін між старим і новим станом.
* **Restorer** — модуль для відновлення об’єкта із змін.
* **Merger** — сервіс для поетапного накладення змін на об’єкт.
* **ContextDTO** — DTO для передачі контексту під час аналізу змін (шлях, тип колекції, атрибути).
* **Attributes** — PHP 8.3 атрибути для управління поведінкою аналізу змін.

---

## 🛠 Встановлення

```bash
composer require ufo-tech/event-sourcing
```

---

## 📦 Компоненти

### Resolver

| Клас                 | Опис                                             |
| -------------------- | ------------------------------------------------ |
| `MainResolver`       | Делегує визначення змін до відповідного resolver |
| `ObjectResolver`     | Працює з об’єктами через Reflection API          |
| `CollectionResolver` | Підтримка асоціативних масивів                   |
| `ArrayResolver`      | Підтримка простих списків                        |
| `ScalarResolver`     | Порівняння скалярних значень                     |

### Restorer & Merger

| Клас               | Опис                                                    |
| ------------------ | ------------------------------------------------------- |
| `Merger`           | Накладає зміни на базовий стан                          |
| `ObjectRestorer`   | Відновлює об’єкт з колекції змін через `DTOTransformer` |
| `ObjectDefinition` | Описує тип об'єкта для відновлення з історії змін       |

### Інтерфейси

| Інтерфейс                      | Призначення                              |
| ------------------------------ | ---------------------------------------- |
| `ResolverInterface`            | Пошук змін між станами                   |
| `MergerInterface`              | Накладання змін на стан                  |
| `RestorerInterface`            | Відновлення об'єкта з історії змін       |
| `MainResolverInterface`        | Інкапсуляція декількох resolver          |
| `MainResolverFactoryInterface` | Фабрика для генерації головного resolver |

---

## 🟠 Атрибути

| Атрибут           | Призначення                           |
| ----------------- | ------------------------------------- |
| `#[ChangeIgnore]` | Ігнорування поля при розрахунку змін  |
| `#[AsCollection]` | Вказує, що поле є колекцією з ключами |

---

## 🟢 Контексти

`ContextDTO` дозволяє передавати додаткову інформацію під час resolve:

* Поточний шлях (path)
* Тип (collection/array/scalar)
* Спецпрапори видалення (`__DELETED__`)
* Підтримка вкладених шляхів при роботі з об’єктами та колекціями

### Правила формування ContextDTO

* `ContextDTO::create()` створює базовий контекст з кореневим шляхом `root`.
* `forPath(string $param)` створює новий контекст із додаванням шляху (наприклад, `root.products`).
* `makeAssocByPath(string $path)` позначає шлях як асоціативний — наприклад, `products` або вкладені `root.products.*`.
* Підтримка шаблонних шляхів із символом `$`, який буде конвертований у патерн `*` для автоматичного визначення колекційних полів на вкладених рівнях.
* Спецпрапор видалення можна змінити через `ContextDTO::create(deletePlaceholder: '__DELETED__')`.

#### Приклади створення ContextDTO:

```php
use Ufo\EventSourcing\Resolver\ContextDTO;

// один шлях
$context = new ContextDTO(assocPaths: ['products']);
$context = new ContextDTO(assocPaths: 'products');

// декілька шляхів
$context = new ContextDTO(assocPaths: ['products', 'items.subitems']);

// з кастомним плейсхолдером видалення
$context = new ContextDTO(deletePlaceholder: '__DELETED__', assocPaths: ['products']);
```

#### Для колекцій зі специфічними ключами або шаблонами:

```php
// Всі subitems у будь-яких items мають сприйматись як колекції
$context = ContextDTO::create()
    ->makeAssocByPath('items.$.subitems');

// products.*.discounts.*.details — асоціативні details в усіх discounts в усіх products
$context = ContextDTO::create()
    ->makeAssocByPath('products.$.discounts.$.details');

// Для колекції на верхньому рівні (root)
$context = ContextDTO::create(assocPaths: ['']);
```

📝 Примітка:

Використання `$` автоматично перетворюється в патерн `*`, і пошук колекцій визначається динамічно для поточного шляху.

---

## 📝 Приклади використання

### Пошук змін

```php
$mainResolver = (new DefaultResolverFactory())->create();
$diff = $mainResolver->resolve($oldObject, $newObject);
```

### Використання контексту з асоціативною колекцією

```php

$context = ContextDTO::create(assocPath: 'products');
$diff = $mainResolver->resolve($old, $new, $context);

// або
$context = ContextDTO::create()->makeAssocByPath('products');
$diff = $mainResolver->resolve($old, $new, $context);
```

### Відновлення об'єкта з історії змін

```php
$restorer = new ObjectRestorer(new Merger());
$restored = $restorer->restore(
    (new ObjectDefinition(MyDto::class))
        ->addChanges($firstChange)
        ->addChanges($secondChange)
);
```

---

## 📚 Застосування

* Event Sourcing та CQRS-архітектури
* Проєкти з DDD-підходом
* Сервісно-орієнтовані архітектури з подієвим зберіганням

---

## 📖 Документація

Детальна документація буде доступна на [docs.ufo-tech.space](https://docs.ufo-tech.space/bin/view/docs/EventSourcing/?language=en)

---

## 📜 Ліцензія

MIT © [UFO Tech](https://ufo-tech.space)

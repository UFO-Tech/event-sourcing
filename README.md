# UFO Event Sourcing

**A lightweight and modern Event Sourcing library for PHP 8.3+**

---

## 🚀 Features

* Stores domain events instead of the current state
* Rebuilds aggregate state through event replay
* Supports projections for building read models
* Provides interfaces for custom event stores and dispatchers

---

## 📖 Core Concepts

* **Resolver** — module for generating differences between old and new state.
* **Restorer** — module for rebuilding an object from changes.
* **Merger** — service for step-by-step application of changes to an object.
* **ContextDTO** — DTO for passing context during change analysis (path, collection type, attributes).
* **Attributes** — PHP 8.3 attributes for controlling change detection behavior.

---

## 🛠 Installation

```bash
composer require ufo-tech/event-sourcing
```

---

## 📦 Components

### Resolver

| Class                | Description                                        |
| -------------------- | -------------------------------------------------- |
| `MainResolver`       | Delegates change detection to appropriate resolver |
| `ObjectResolver`     | Handles objects via Reflection API                 |
| `CollectionResolver` | Supports associative arrays                        |
| `ArrayResolver`      | Handles indexed arrays                             |
| `ScalarResolver`     | Compares scalar values                             |

### Restorer & Merger

| Class              | Description                                                          |
| ------------------ | -------------------------------------------------------------------- |
| `Merger`           | Merges changes into the base state                                   |
| `ObjectRestorer`   | Rebuilds an object from a collection of changes via `DTOTransformer` |
| `ObjectDefinition` | Defines the object type to restore from change history               |

### Interfaces

| Interface                      | Purpose                                |
| ------------------------------ | -------------------------------------- |
| `ResolverInterface`            | Detects changes between states         |
| `MergerInterface`              | Merges changes into a state            |
| `RestorerInterface`            | Restores an object from change history |
| `MainResolverInterface`        | Encapsulates multiple resolvers        |
| `MainResolverFactoryInterface` | Factory for creating the main resolver |

---

## 🟠 Attributes

| Attribute         | Purpose                                 |
| ----------------- | --------------------------------------- |
| `#[ChangeIgnore]` | Ignores a field during change detection |
| `#[AsCollection]` | Marks a field as a key-based collection |

---

## 🟢 Contexts

`ContextDTO` allows passing additional metadata during resolving:

* Current path (`path`)
* Type (collection/array/scalar)
* Special delete placeholder (`__DELETED__`)
* Supports nested paths for objects and collections

### ContextDTO Rules

* `ContextDTO::create()` creates a base context with root path `root`.
* `forPath(string $param)` adds a path segment (e.g., `root.products`).
* `makeAssocByPath(string $path)` marks a path as associative (e.g., `products`, `root.products.*`).
* Supports pattern paths with `$` that converts to `*` for nested collections.
* Delete placeholder can be customized via `ContextDTO::create(deletePlaceholder: '__DELETED__')`.

#### Examples:

```php
use Ufo\EventSourcing\Resolver\ContextDTO;

// Single path
$context = new ContextDTO(assocPaths: ['products']);
$context = new ContextDTO(assocPaths: 'products');

// Multiple paths
$context = new ContextDTO(assocPaths: ['products', 'items.subitems']);

// Custom delete placeholder
$context = new ContextDTO(deletePlaceholder: '__DELETED__', assocPaths: ['products']);
```

#### Using patterns:

```php
// All subitems inside any items are treated as collections
$context = ContextDTO::create()
    ->makeAssocByPath('items.$.subitems');

// products.*.discounts.*.details are treated as associative
$context = ContextDTO::create()
    ->makeAssocByPath('products.$.discounts.$.details');

// Root-level collection
$context = ContextDTO::create(assocPaths: ['']);
```

📝 Note: `$` automatically converts to `*` for wildcard path matching.

---

## 📝 Usage Examples

### Detecting Changes

```php
$mainResolver = (new DefaultResolverFactory())->create();
$diff = $mainResolver->resolve($oldObject, $newObject);
```

### Using context for associative collection

```php
$context = ContextDTO::create()->makeAssocByPath('products');
$diff = $mainResolver->resolve($old, $new, $context);
```

### Restoring an object from change history

```php
$restorer = new ObjectRestorer(new Merger());
$restored = $restorer->restore(
    (new ObjectDefinition(MyDto::class))
        ->addChanges($firstChange)
        ->addChanges($secondChange)
);
```

---

## 📚 Use Cases

* Event Sourcing and CQRS architectures
* Domain-Driven Design (DDD) based projects
* Service-oriented systems with event-based persistence

---

## 📖 Documentation

Full documentation available at [docs.ufo-tech.space](https://docs.ufo-tech.space/bin/view/docs/EventSourcing/?language=en)

---

## 📜 License

MIT © [UFO Tech](https://ufo-tech.space)

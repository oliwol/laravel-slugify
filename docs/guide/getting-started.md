# Getting Started

## Installation

Install the package via Composer:

```bash
composer require oliwol/laravel-slugify
```

## Quick Start

### Attribute-only (the simplest setup)

Add the `#[Slugify]` attribute and register the model in `config/slugify.php` — no trait required. The package generates slugs automatically on save.

```php
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    // No trait required
}
```

```php
// config/slugify.php (publish with: php artisan vendor:publish --tag=slugify-config)
'models' => [
    App\Models\Post::class,
],
```

### With the `HasSlug` trait

Add the trait when you need `findBySlug()`, [slug history](/guide/features#slug-history), [translatable slugs](/guide/features#translatable-slugs), [route binding](/guide/features#route-model-binding) or [ID-anchored slugs](/guide/id-anchored-slugs). No config registration is needed when using the trait.

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug;
}
```

### Closures and complex configuration

When a PHP attribute isn't expressive enough — closures, conditional logic — return a [`SlugConfig`](/guide/configuration#via-the-fluent-slugconfig-api) from a `slugConfig()` method, or override the configuration methods directly.

::: tip Priority
Method overrides > `slugConfig()` > `#[Slugify]` attribute.
:::

## Migration Setup

Make sure your table contains the slug column:

```php
$table->string('slug')->unique();
```

If you use [scoping](/guide/features#custom-scoping), you probably don't want a global unique index. Example for per-tenant uniqueness:

```php
$table->unique(['tenant_id', 'slug']);
```

## How It Works

The `HasSlug` trait hooks into Eloquent's `saving` event:

1. **Resolve the source** — from the `#[Slugify]` attribute or a `getAttributeToCreateSlugFrom()` override.
2. **Generate a slug** — if the source is a method, call it; otherwise combine filled attribute values.
3. **Skip regeneration** if source attributes are unchanged or the slug was manually set.
4. **Ensure uniqueness** by incrementing (`my-post`, `my-post-2`, `my-post-3`, ...).

::: info Method Sources
When using a method source, dirty detection is skipped — the slug is always regenerated on save, since the trait cannot track the method's dependencies.
:::

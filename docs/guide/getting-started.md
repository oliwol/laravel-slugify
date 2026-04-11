# Getting Started

## Installation

Install the package via Composer:

```bash
composer require oliwol/laravel-slugify
```

## Quick Start

### Using the PHP Attribute (recommended)

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug;
}
```

### Using method overrides

```php
use Oliwol\Slugify\HasSlug;

class Post extends Model
{
    use HasSlug;

    public function getAttributeToCreateSlugFrom(): string|array
    {
        return 'title';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

::: tip Priority
Method overrides always take precedence over the `#[Slugify]` attribute.
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

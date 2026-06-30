---
name: slugify-development
description: Generate URL-friendly slugs for Eloquent models with oliwol/laravel-slugify. Use when adding automatic slug generation, slug-based route binding, slug history redirects, translatable slugs, or ID-anchored (self-healing) URLs to a Laravel model.
---

# Slugify Development

This package generates unique slugs for Eloquent models automatically on save. Configuration is driven by a `#[Slugify]` PHP attribute or the fluent `SlugConfig` API. A `HasSlug` trait adds query helpers and route-key behavior; it is optional for simple cases.

## When to use this skill

Use this skill when working with the `oliwol/laravel-slugify` package — adding slugs to a model, choosing between attribute and trait usage, configuring separators/length/uniqueness, or setting up slug-based routing, redirects, or ID-anchored URLs.

## Migration

The slug needs a column. Make it nullable (it is filled on save) and usually unique:

```php
Schema::table('posts', function (Blueprint $table): void {
    $table->string('slug')->nullable()->unique();
});
```

For translatable slugs use a `json` column instead of `string`.

## Quick start

### Attribute-only (no trait)

Best for simple models that only need automatic slug generation. Register the model in `config/slugify.php` so the service provider hooks the `saving` event:

```php
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    // No trait required
}
```

```php
// config/slugify.php
'models' => [
    App\Models\Post::class,
],
```

### Trait-based

Add `HasSlug` when you need query helpers or slug-based routing:

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug;
}
```

## `#[Slugify]` parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `from` | `string\|array\|string` | required | Source: an attribute name, an array of attribute names, or a method name on the model |
| `to` | `?string` | `null` | Target column. Falls back to `getRouteKeyName()` |
| `separator` | `?string` | `'-'` | Word separator |
| `maxLength` | `?int` | `null` | Max length, truncated at word boundaries |
| `regenerateOnUpdate` | `bool` | `true` | Regenerate when the source changes on update; set `false` to lock the slug after creation |
| `routeBinding` | `bool` | `false` | Bind routes by the slug column. Requires `to`. Needs `HasSlug` |
| `appendId` | `bool` | `false` | ID-anchored slugs (`{slug}-{id}`). Needs `HasSlug` |

```php
#[Slugify(from: 'title', to: 'slug', separator: '_', maxLength: 60, regenerateOnUpdate: false)]
```

## `SlugConfig` fluent API

Use `SlugConfig` (returned from a `slugConfig()` method) when you need a **closure** source or conditional logic — things a PHP attribute cannot express. Precedence: method override > `slugConfig()` > `#[Slugify]`.

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\SlugConfig;

class Post extends Model
{
    use HasSlug;

    public function slugConfig(): SlugConfig
    {
        return SlugConfig::create()
            ->from(fn (self $post): string => $post->category->name.' '.$post->title)
            ->to('slug')
            ->separator('-')
            ->maxLength(60)
            ->regenerateOnUpdate()
            ->routeBinding()
            ->appendId();
    }
}
```

A closure source receives the model instance. Like a method source, it skips dirty detection (the slug is regenerated on every save unless `regenerateOnUpdate(false)`).

## When `HasSlug` is required

The trait is required for anything beyond plain attribute-only generation:

- `findBySlug()` / `findBySlugOrFail()` query helpers
- `routeBinding: true` (slug-based route model binding)
- `appendId: true` (ID-anchored slugs)
- Slug history (`HasSlugHistory`)
- Translatable slugs (`HasTranslatableSlug`)

## Common patterns

### Multi-field slug

```php
#[Slugify(from: ['first_name', 'last_name'], to: 'slug')]
```

### Method source

```php
#[Slugify(from: 'getFullTitle', to: 'slug')]
class Post extends Model
{
    use HasSlug;

    public function getFullTitle(): string
    {
        return $this->category->name.' '.$this->title;
    }
}
```

### Scoped uniqueness (e.g. per tenant)

Override `scopeSlugQuery()` so uniqueness checks are scoped:

```php
public function scopeSlugQuery($query)
{
    return $query->where('tenant_id', $this->tenant_id);
}
```

### Slug history redirects (301)

Add `HasSlugHistory`, publish the `slug_history` migration, and use the `slug.redirect` middleware. Old slugs redirect to the current one with a `301` (configurable).

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\HasSlugHistory;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug, HasSlugHistory;
}
```

### ID-anchored URLs (`appendId`)

The route key becomes `{slug}-{id}` and the model resolves by the trailing ID, so slugs can change without breaking links. Stale slugs issue a `308` canonical redirect via the `slug.redirect` middleware. No extra DB table.

```php
#[Slugify(from: 'title', to: 'slug', appendId: true)]
class Post extends Model
{
    use HasSlug;
}

// /posts/hello-world-5 → 200
// after the title changes: /posts/hello-world-5 → 308 → /posts/updated-title-5
```

Choose `appendId` for zero-maintenance redirects without an extra table; choose `HasSlugHistory` for clean ID-free URLs with a full audit trail. The two are orthogonal and can coexist.

### Bulk generation

Generate or regenerate slugs for existing rows:

```bash
php artisan slugify:generate "App\Models\Post"
php artisan slugify:generate "App\Models\Post" --force
```

## Naming conventions

This package uses its own terminology — do **not** use Spatie's:

- `appendId: true` — **not** `selfHealing`
- `SlugConfig` — **not** `SlugOptions`
- "ID-anchored slugs" — **not** "self-healing URLs"
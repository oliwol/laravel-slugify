# 🌀 Laravel Slugify

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oliwol/laravel-slugify.svg?style=flat-square)](https://packagist.org/packages/oliwol/laravel-slugify)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/oliwol/laravel-slugify/.github/workflows/tests.yml?branch=1.x&label=tests&style=flat-square)](https://github.com/oliwol/laravel-slugify/actions)
[![License](https://img.shields.io/packagist/l/oliwol/laravel-slugify.svg?style=flat-square)](https://github.com/oliwol/laravel-slugify/blob/1.x/LICENSE)

A tiny trait that gives your Eloquent models clean, automatic slugs — without setup, ceremony, or extra weight.

Attach it to a model, define the source attribute, and the trait quietly handles generation, updates and uniqueness.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require oliwol/laravel-slugify
```

## ⚡️ Quick Start

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

> **Priority**: Method overrides always take precedence over the `#[Slugify]` attribute.

## 🛠️ Usage
Add the ```HasSlug``` trait to any Eloquent model where a slug should be automatically generated.

### Configuration via `#[Slugify]` Attribute

The `#[Slugify]` attribute accepts the following parameters:

* `from` (required) — the attribute(s) used to generate the slug. Accepts a single string (e.g. `'name'`) or an array of strings (e.g. `['first_name', 'last_name']`).
* `to` (optional) — the column to save the slug to. Falls back to `getRouteKeyName()` if omitted.
* `separator` (optional) — the character used to separate words in the slug. Defaults to `'-'`.
* `maxLength` (optional) — maximum number of characters for the slug. Truncates at word boundaries. Defaults to `null` (no limit).
* `regenerateOnUpdate` (optional) — whether to regenerate the slug when the source attribute changes on update. Defaults to `true`. Set to `false` to only generate slugs on creation (useful for SEO).

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

// Full configuration via attribute
#[Slugify(from: 'name', to: 'slug')]
class Post extends Model
{
    use HasSlug;
}

// Only 'from' — slug column is determined by getRouteKeyName()
#[Slugify(from: 'name')]
class Post extends Model
{
    use HasSlug;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

// Multiple source attributes — generates slug from combined values
#[Slugify(from: ['first_name', 'last_name'], to: 'slug')]
class Author extends Model
{
    use HasSlug;
}
// first_name: "John", last_name: "Doe" → "john-doe"

// Custom separator — uses underscores instead of hyphens
#[Slugify(from: 'title', to: 'slug', separator: '_')]
class Post extends Model
{
    use HasSlug;
}
// "Hello World" → "hello_world"

// SEO-safe — slug is only generated on creation, never updated
#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
class Post extends Model
{
    use HasSlug;
}
```

> **Note**: The `to` parameter only controls where the slug is saved. For route model binding, you still need to override `getRouteKeyName()` separately on your model.

### Configuration via methods

Alternatively, you can configure slug generation by overriding methods:

* ```getAttributeToCreateSlugFrom()``` — the attribute(s) used to generate the slug. Return a `string` or `array<string>`.
* ```getRouteKeyName()``` — the slug column for route model binding (e.g. slug).
* Optionally ```getAttributeToSaveSlugTo()``` — a different column to save the slug.
* Optionally ```getSlugSeparator()``` — the separator character (default `'-'`).
* Optionally ```getMaxSlugLength()``` — maximum slug length, truncated at word boundaries (default `null`).
* Optionally ```shouldRegenerateSlugOnUpdate()``` — return `false` to only generate slugs on creation (default `true`).
* Optionally override ```scopeSlugQuery()``` — scoping for uniqueness (e.g. per team).

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

class Post extends Model
{
    use HasSlug;

    /**
     * Attribute(s) used for generating the slug.
     * Return a string or an array of strings.
     */
    public function getAttributeToCreateSlugFrom(): string|array
    {
        return 'name';
    }

    /**
     * Use slug for route binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * This package uses Laravel's getRouteKeyName to store the slug.
     * If you are using a different column for your routes,
     * use getAttributeToSaveSlugTo to store the slug.
     */
    public function getAttributeToSaveSlugTo(): string
    {
        return 'slug';
    }

    /**
     * Scope applied when checking for uniqueness.
     */
    public function scopeSlugQuery($query)
    {
        return $query->where('tenant_id', 1);
    }
}
```

Make sure your table contains the slug column:

```php
$table->string('slug')->unique();
```

If you use scoping, you probably don’t want a global unique index.
Example: slugs must be unique per tenant:

```php
$table->unique(['tenant_id', 'slug']);
```

## ⚙️ How it works

The ```HasSlug``` trait hooks into the Eloquent saving event:

```php
protected static function bootHasSlug(): void
 {
     static::saving(function (Model $model): void {
         if ($model->isSluggable()) {
             $model->createSlug();
         }
     });
 }
```

When triggered, it will:

1. Resolve the source attribute(s) — from the `#[Slugify]` attribute or a `getAttributeToCreateSlugFrom()` override. Supports a single attribute or multiple attributes.
2. Generate a slug by combining filled source values (null/empty values are skipped).
3. Skip regeneration if:
   1. None of the source attributes are dirty (unchanged), or
   2. The slug has been manually set and differs from the original.
4. Ensure uniqueness by incrementing existing slugs (my-post, my-post-2, my-post-3, …).

## 🔎 Finding Models by Slug

The trait provides two static methods to look up models by their slug:

```php
// Returns the model or null
$post = Post::findBySlug('hello-world');

// Returns the model or throws ModelNotFoundException
$post = Post::findBySlugOrFail('hello-world');
```

Both methods respect the configured slug column (`to` / `getAttributeToSaveSlugTo()`) and apply `scopeSlugQuery()` for scoped lookups.

## ✅ Best practices & caveats

- Ensure the route key column (```getRouteKeyName()```) is present in your table and is not the primary key (unless intentionally designed).
- If you manually set a slug, the trait will not override it. Use this to allow user-edited slugs.

## 🔍 Custom Scoping Example

To ensure slugs are unique per tenant, override the `scopeSlugQuery()` method:

```php
public function scopeSlugQuery($query)
{
    return $query->where('tenant_id', 1);
}
```
This will append a `WHERE tenant_id = ?` clause when checking for existing slugs.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
# Migrating from Spatie

This guide helps you migrate from [`spatie/laravel-sluggable`](https://github.com/spatie/laravel-sluggable) to `oliwol/laravel-slugify`.

Both packages are mature and share a lot of ground — especially since Spatie v4, which added attribute-based configuration and self-healing URLs. This guide is written to be **factual, not competitive**: it shows where the packages are equivalent, where each is stronger, and exactly how to translate one API to the other.

## Honest positioning vs Spatie v4

The two packages overlap heavily. The differences worth knowing:

- **Laravel Slugify is stronger at**: slug history with timestamps and an audit trail, lifecycle events (`SlugGenerated` / `SlugUpdated`), a built-in Artisan bulk-generation command, and a richer attribute (separator, max length, regeneration control and route binding all live on `#[Slugify]` itself).
- **Spatie v4 is stronger at**: overridable actions — you can swap the slug generator or the self-healing URL resolver for your own class via config. Laravel Slugify has no equivalent today; use events or method overrides instead.

If those specific features don't matter to you, both packages will serve you equally well.

## Feature comparison

| Feature | Laravel Slugify v2 | Spatie v4 |
|---|---|---|
| Attribute-only (no trait) | ✅ `#[Slugify]` | ✅ `#[Sluggable]` |
| Options on the attribute itself | ✅ separator, maxLength, regenerate, routeBinding, appendId | ⚠️ `from` / `to` / `selfHealing` only |
| Fluent config API | ✅ `SlugConfig` | ✅ `SlugOptions` |
| Closures as source | ✅ (`SlugConfig`) | ✅ (`SlugOptions`) |
| Multiple source fields | ✅ | ✅ |
| Custom separator | ✅ | ✅ |
| Max length (word-boundary aware) | ✅ | ✅ |
| Prevent regeneration on update | ✅ | ✅ |
| Scoped uniqueness | ✅ `scopeSlugQuery()` | ✅ (`SlugOptions`) |
| Slug history with timestamps | ✅ `HasSlugHistory` | ❌ |
| Events (`SlugGenerated`, `SlugUpdated`) | ✅ | ❌ |
| Artisan bulk generation command | ✅ `slugify:generate` | ❌ |
| Translatable slugs | ✅ | ✅ |
| ID-anchored URLs | ✅ `appendId` | ✅ ("Self-Healing URLs") |
| Laravel Boost skill | ✅ | ✅ |
| Overridable actions | ❌ | ✅ |

## API mapping

The fastest way to migrate is to translate names one-to-one:

| Spatie v4 | Laravel Slugify v2 |
|---|---|
| `#[Sluggable]` | `#[Slugify]` |
| `from:` / `to:` | `from:` / `to:` |
| `selfHealing: true` | `appendId: true` |
| `SlugOptions` / `getSlugOptions()` | `SlugConfig` / `slugConfig()` |
| `->generateSlugsFrom('title')` | `->from('title')` |
| `->saveSlugsTo('slug')` | `->to('slug')` |
| `->usingSeparator('_')` | `->separator('_')` |
| `->slugsShouldBeNoLongerThan(50)` | `->maxLength(50)` |
| `->doNotGenerateSlugsOnUpdate()` | `->regenerateOnUpdate(false)` |
| `Spatie\Sluggable\HasSlug` | `Oliwol\Slugify\HasSlug` |
| `Spatie\Sluggable\HasTranslatableSlug` | `Oliwol\Slugify\HasTranslatableSlug` |

## Side-by-side

### Basic configuration (attribute)

Both packages support attribute-only configuration since Spatie v4.

::: code-group

```php [spatie/laravel-sluggable v4]
use Spatie\Sluggable\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model {}
```

```php [oliwol/laravel-slugify]
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model {}
```

:::

### Separator, max length and regeneration

These options live directly on the `#[Slugify]` attribute. In Spatie v4 they require the `HasSlug` trait and a `getSlugOptions()` method.

::: code-group

```php [spatie/laravel-sluggable v4]
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingSeparator('_')
            ->slugsShouldBeNoLongerThan(50)
            ->doNotGenerateSlugsOnUpdate();
    }
}
```

```php [oliwol/laravel-slugify]
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug', separator: '_', maxLength: 50, regenerateOnUpdate: false)]
class Post extends Model {}
```

:::

### Multiple source fields

::: code-group

```php [spatie/laravel-sluggable v4]
SlugOptions::create()
    ->generateSlugsFrom(['first_name', 'last_name'])
    ->saveSlugsTo('slug');
```

```php [oliwol/laravel-slugify]
#[Slugify(from: ['first_name', 'last_name'], to: 'slug')]
```

:::

### Closures / custom slug generation

Both packages support closures. Spatie uses `getSlugOptions()`; Laravel Slugify uses `slugConfig()` returning a `SlugConfig`.

::: code-group

```php [spatie/laravel-sluggable v4]
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (self $post) => $post->category->name.' '.$post->title)
            ->saveSlugsTo('slug');
    }
}
```

```php [oliwol/laravel-slugify]
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\SlugConfig;

class Post extends Model
{
    use HasSlug;

    public function slugConfig(): SlugConfig
    {
        return SlugConfig::create()
            ->from(fn (self $post) => $post->category->name.' '.$post->title)
            ->to('slug');
    }
}
```

:::

### Self-healing / ID-anchored URLs

Same feature, different name. Both produce `hello-world-5` URLs that resolve by the ID and issue a `308` redirect when the slug part is stale.

::: code-group

```php [spatie/laravel-sluggable v4]
#[Sluggable(from: 'title', to: 'slug', selfHealing: true)]
class Post extends Model
{
    use HasSlug;
}
```

```php [oliwol/laravel-slugify]
#[Slugify(from: 'title', to: 'slug', appendId: true)]
class Post extends Model
{
    use HasSlug;
}
```

:::

See [ID-Anchored Slugs](/guide/id-anchored-slugs) for details.

## Migration steps

### From Spatie v4 (attribute-based)

1. **Replace the dependency**
   ```bash
   composer remove spatie/laravel-sluggable
   composer require oliwol/laravel-slugify
   ```

2. **Rename the attribute** on each model: `#[Sluggable(...)]` → `#[Slugify(...)]`, and `selfHealing:` → `appendId:`.

3. **Update imports**: `Spatie\Sluggable\HasSlug` → `Oliwol\Slugify\HasSlug` (and `HasTranslatableSlug` likewise).

4. **Translate `getSlugOptions()`** (if used) to a `slugConfig()` method returning `SlugConfig`, or move simple options onto the `#[Slugify]` attribute. Use the [API mapping](#api-mapping) above.

5. **Test your application** — existing slugs in the database are not affected.

### From Spatie v3 (`getSlugOptions`-based)

1. **Replace the dependency** (as above).

2. **Update imports**: `Spatie\Sluggable\HasSlug` → `Oliwol\Slugify\HasSlug`; remove `use Spatie\Sluggable\SlugOptions;` and add `use Oliwol\Slugify\Slugify;`.

3. **Replace `getSlugOptions()`** with a `#[Slugify]` attribute for simple configurations, or a `slugConfig()` method returning `SlugConfig` if you need closures or conditional logic.

4. **Update translatable slugs** (if applicable): `Spatie\Sluggable\HasTranslatableSlug` → `Oliwol\Slugify\HasTranslatableSlug`.

5. **Test your application** — existing slugs are preserved.

## Common gotchas

### Spatie's attribute is minimal

In Spatie v4 the `#[Sluggable]` attribute only accepts `from`, `to` and `selfHealing`; everything else (separator, max length, regeneration, scoping) lives in `getSlugOptions()`. With Laravel Slugify those options are available directly on `#[Slugify]`, so many models that needed a `getSlugOptions()` method in Spatie can become a single attribute here.

### `getSlugOptions()` is not called

Laravel Slugify does not recognise Spatie's `getSlugOptions()` convention. Remove it and use `#[Slugify]` or `slugConfig()` instead, otherwise the method silently does nothing.

### No overridable actions

Spatie v4 lets you swap its slug generator or self-healing resolver via config. Laravel Slugify has no equivalent. If you relied on this, replace it with a method source / closure (`slugConfig()->from(...)`) for custom generation, or listen to `SlugGenerated` / `SlugUpdated` for side effects.

### Scoping works differently

Spatie configures scoped uniqueness in `getSlugOptions()`. Laravel Slugify uses a `scopeSlugQuery()` method:

```php
public function scopeSlugQuery($query)
{
    return $query->where('tenant_id', $this->tenant_id);
}
```

### Existing database slugs are preserved

Switching packages does **not** touch existing slugs. New slugs are only generated on save. To regenerate everything with the new configuration:

```bash
php artisan slugify:generate "App\Models\Post" --force
```
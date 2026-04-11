# Migrating from Spatie

This guide helps you migrate from [`spatie/laravel-sluggable`](https://github.com/spatie/laravel-sluggable) to `oliwol/laravel-slugify`.

## Side-by-Side Comparison

### Basic Configuration

::: code-group

```php [spatie/laravel-sluggable]
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

```php [oliwol/laravel-slugify]
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug;
}
```

:::

### Multiple Source Fields

::: code-group

```php [spatie/laravel-sluggable]
SlugOptions::create()
    ->generateSlugsFrom(['first_name', 'last_name'])
    ->saveSlugsTo('slug');
```

```php [oliwol/laravel-slugify]
#[Slugify(from: ['first_name', 'last_name'], to: 'slug')]
```

:::

### Custom Separator

::: code-group

```php [spatie/laravel-sluggable]
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->usingSeparator('_');
```

```php [oliwol/laravel-slugify]
#[Slugify(from: 'title', to: 'slug', separator: '_')]
```

:::

### Max Length

::: code-group

```php [spatie/laravel-sluggable]
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->slugsShouldBeNoLongerThan(50);
```

```php [oliwol/laravel-slugify]
#[Slugify(from: 'title', to: 'slug', maxLength: 50)]
```

:::

### Prevent Regeneration on Update

::: code-group

```php [spatie/laravel-sluggable]
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->doNotGenerateSlugsOnUpdate();
```

```php [oliwol/laravel-slugify]
#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
```

:::

### Custom Slug Generation

::: code-group

```php [spatie/laravel-sluggable]
SlugOptions::create()
    ->generateSlugsFrom(function ($model) {
        return $model->category->name . ' ' . $model->title;
    })
    ->saveSlugsTo('slug');
```

```php [oliwol/laravel-slugify]
#[Slugify(from: 'getFullTitle', to: 'slug')]
class Post extends Model
{
    use HasSlug;

    public function getFullTitle(): string
    {
        return $this->category->name . ' ' . $this->title;
    }
}
```

:::

## Migration Checklist

1. **Replace the dependency**
   ```bash
   composer remove spatie/laravel-sluggable
   composer require oliwol/laravel-slugify
   ```

2. **Update imports** in all model files:
   - `Spatie\Sluggable\HasSlug` → `Oliwol\Slugify\HasSlug`
   - Remove `use Spatie\Sluggable\SlugOptions;`
   - Add `use Oliwol\Slugify\Slugify;`

3. **Replace `getSlugOptions()`** with `#[Slugify]` attribute on the class

4. **Update translatable slugs** (if applicable):
   - `Spatie\Sluggable\HasTranslatableSlug` → `Oliwol\Slugify\HasTranslatableSlug`

5. **Test your application** — existing slugs in the database are not affected

## Feature Comparison

| Feature | spatie/laravel-sluggable | oliwol/laravel-slugify |
|---|---|---|
| Attribute-based config | No | Yes (`#[Slugify]`) |
| Method-based config | `getSlugOptions()` | Method overrides |
| Multiple source fields | Yes | Yes |
| Custom separator | Yes | Yes |
| Max length | Yes | Yes (word-boundary aware) |
| Prevent update regeneration | Yes | Yes |
| Slug uniqueness | Yes | Yes |
| Custom scoping | No | Yes (`scopeSlugQuery`) |
| Slug history | No | Yes (`HasSlugHistory`) |
| Events | No | Yes (`SlugGenerated`, `SlugUpdated`) |
| `findBySlug()` | Yes | Yes |
| Translatable slugs | Yes | Yes |
| Artisan command | No | Yes (`slugify:generate`) |
| Closure source | Yes | Via method source |

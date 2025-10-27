# 🌀 Laravel Slugify

A lightweight, framework-native **Laravel Eloquent trait** that automatically generates and maintains unique slugs for your models.  
It requires **no external dependencies**, uses Laravel’s native `Str::slug()` helper, and gracefully handles dirty attributes and manual overrides.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require oliwol/slugify
```

## 🛠️ Usage
1. Add the HasSlug trait to any Eloquent model that should have an automatically managed slug.
2. Implement the required getSlugKeyName() method.
3. Ensure your model has a slug column (e.g. slug) and that your route key name uses it.

```php
use Oliwol\Slugify\HasSlug;

class Post extends Model
{
    use HasSlug;
    
    /**
     * Get the attribute to be used for slug generation.
     */
    public function getSlugifyKeyName(): string
    {
        return 'name';
    }
    
    /**
     * Get the route key name for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

## ⚙️ How it works

The ```HasSlug``` trait hooks into the Eloquent creating and updating events:

```php
protected static function bootHasSlug(): void
{
    static::creating(fn (Model $model) => $model->createSlug());
    static::updating(fn (Model $model) => $model->createSlug());
}
```

When triggered, it will:

1. Generate a slug from the attribute defined by getSlugKeyName().
2. Skip regeneration if:
   1. The source attribute is not dirty (unchanged), or 
   2. The slug has been manually set and differs from the original.
3. Ensure uniqueness by incrementing existing slugs (my-post, my-post-2, my-post-3, …).

## ✅ Best practices & caveats

- Ensure the route key column (getRouteKeyName()) is present in your table and is not the primary key (unless intentionally designed).
- If you manually set a slug, the trait will not override it. Use this to allow user-edited slugs.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
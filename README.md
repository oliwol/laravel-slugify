# 🌀 Laravel Slugify

A lightweight, framework-native **Laravel Eloquent trait** that automatically generates and maintains unique slugs for your models.  
It requires **no external dependencies**, uses Laravel’s native `Str::slug()` helper, and gracefully handles dirty attributes, manual overrides, and custom scoping.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require oliwol/laravel-slugify
```

## 🛠️ Usage
Add the ```HasSlug``` trait to any Eloquent model where a slug should be automatically generated and kept unique.

You must implement:

* ```getAttributeToCreateSlugFrom()``` — the attribute used to generate the slug (e.g. name/title).
* ```getRouteKeyName()``` — the slug column for route model binding (e.g. slug).
* Optionally ```getAttributeToSaveSlugTo()``` — a different column to save the slug.
* Optionally override ```getSlugScope()``` — scoping for uniqueness (e.g. per user, per company, per team).

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

class Post extends Model
{
    use HasSlug;

    /**
     * Attribute used for generating the slug.
     */
    public function getAttributeToCreateSlugFrom(): string
    {
        return 'name';
    }

    /**
     * Attribute where the slug is saved.
     */
    public function getAttributeToSaveSlugTo(): string
    {
        return 'slug';
    }

    /**
     * Scope applied when checking for uniqueness.
     *
     * Example: all slugs must be unique per user_id.
     */
    public function getSlugScope(): Builder
    {
        return fn (Builder $query): Builder => $query->where('user_id', $this->user_id);
    }

    /**
     * Use slug for route binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
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
    static::saving(fn (Model $model) => $model->createSlug());
}
```

When triggered, it will:

1. Generate a slug from the attribute defined by ```getAttributeToCreateSlugFrom()```.
2. Skip regeneration if:
   1. The source attribute is not dirty (unchanged), or 
   2. The slug has been manually set and differs from the original.
3. Ensure uniqueness by incrementing existing slugs (my-post, my-post-2, my-post-3, …).

## ✅ Best practices & caveats

- Ensure the route key column (getRouteKeyName()) is present in your table and is not the primary key (unless intentionally designed).
- If you manually set a slug, the trait will not override it. Use this to allow user-edited slugs.

## 🔍 Custom Scoping Example

To ensure slugs are unique per tenant, override the `getSlugScope()` method:

```phpphp
public function getSlugScope(): Builder
{
    return fn (Builder $query): Builder => $query->where('tenant_id', 1);
}
```
This will append a `WHERE tenant_id = ?` clause when checking for existing slugs.

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).
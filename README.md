# 🌀 Laravel Slugify

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oliwol/laravel-slugify.svg?style=flat-square)](https://packagist.org/packages/oliwol/laravel-slugify)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/oliwol/laravel-slugify/.github/workflows/tests.yml?branch=1.x&label=tests&style=flat-square)](https://github.com/oliwol/laravel-slugify/actions)
[![License](https://img.shields.io/packagist/l/oliwol/laravel-slugify.svg?style=flat-square)](https://github.com/oliwol/laravel-slugify/blob/1.x/LICENSE)

**[Documentation](https://oliwol.github.io/laravel-slugify/)** | **[Migrating from Spatie](https://oliwol.github.io/laravel-slugify/guide/migrating-from-spatie)**

A tiny trait that gives your Eloquent models clean, automatic slugs — without setup, ceremony, or extra weight.

Attach it to a model, define the source (an attribute, multiple attributes, or a method), and the trait quietly handles generation, updates and uniqueness.

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

* `from` (required) — the source for the slug. Accepts a single attribute name (e.g. `'name'`), an array of attribute names (e.g. `['first_name', 'last_name']`), or a method name on the model (e.g. `'getFullTitle'`). When a method name is given, it is called to produce the slug string and dirty detection is skipped (the slug is always regenerated on save).
* `to` (optional) — the column to save the slug to. Falls back to `getRouteKeyName()` if omitted.
* `separator` (optional) — the character used to separate words in the slug. Defaults to `'-'`.
* `maxLength` (optional) — maximum number of characters for the slug. Truncates at word boundaries. Defaults to `null` (no limit).
* `regenerateOnUpdate` (optional) — whether to regenerate the slug when the source attribute changes on update. Defaults to `true`. Set to `false` to only generate slugs on creation (useful for SEO).
* `routeBinding` (optional) — when `true`, automatically sets `getRouteKeyName()` to the slug column. Requires `to:` to be set explicitly. Defaults to `false`.

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

// Method source — use a model method for complex slug generation
#[Slugify(from: 'getFullTitle', to: 'slug')]
class Post extends Model
{
    use HasSlug;

    public function getFullTitle(): string
    {
        return $this->category->name . ' ' . $this->title;
    }
}
// category: "Tech", title: "Laravel Tips" → "tech-laravel-tips"
// Note: dirty detection is skipped — the slug is always regenerated on save.

// SEO-safe — slug is only generated on creation, never updated
#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
class Post extends Model
{
    use HasSlug;
}
```

> **Route model binding**: Use `routeBinding: true` to automatically configure `getRouteKeyName()` without a manual override. Requires `to:` to be set.

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

1. Resolve the source — from the `#[Slugify]` attribute or a `getAttributeToCreateSlugFrom()` override. Supports a single attribute, multiple attributes, or a model method name.
2. Generate a slug — if the source is a method, call it; otherwise combine filled attribute values (null/empty values are skipped).
3. Skip regeneration if:
   1. Using attribute source(s) and none are dirty (unchanged), or
   2. The slug has been manually set and differs from the original.
   
   > **Note**: When using a method source, dirty detection is skipped — the slug is always regenerated on save, since the trait cannot track the method's dependencies.
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

## 📜 Slug History

When slugs change (e.g. because a title was updated), old URLs break. The optional `HasSlugHistory` trait keeps track of previous slugs so you can implement 301 redirects from old URLs to new ones — critical for SEO.

### Setup

Publish and run the migration:

```bash
php artisan vendor:publish --tag=slugify-migrations
php artisan migrate
```

This creates a `slug_history` table that stores old slugs with a polymorphic relation to your models.

### Usage

Add the `HasSlugHistory` trait alongside `HasSlug`:

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\HasSlugHistory;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug, HasSlugHistory;
}
```

When a slug changes, the old slug is automatically recorded in the `slug_history` table. Duplicate entries are prevented — if a model cycles back to a previous slug, it won't be stored again.

### Finding models by current or historical slug

Use `findBySlugWithHistory()` to look up a model by its current slug or any previous slug:

```php
$post = Post::create(['title' => 'Laravel Tips']);
// slug: "laravel-tips"

$post->update(['title' => 'Advanced Laravel Tips']);
// slug: "advanced-laravel-tips"
// history: ["laravel-tips"]

// Find by current slug
Post::findBySlugWithHistory('advanced-laravel-tips'); // → Post

// Find by old slug (useful for 301 redirects)
Post::findBySlugWithHistory('laravel-tips'); // → Post

// Returns null if neither current nor historical slug matches
Post::findBySlugWithHistory('nonexistent'); // → null
```

### Implementing 301 redirects

The easiest way is the `slug.redirect` middleware. Add it to any route that uses slug-based route model binding — it handles the redirect automatically:

```php
Route::get('/posts/{post:slug}', PostController::class)
    ->middleware(['slug.redirect']);
```

The redirect status defaults to `301`. Publish the config to change it:

```bash
php artisan vendor:publish --tag=slugify-config
```

Alternatively, handle the redirect manually in the controller:

```php
public function show(string $slug)
{
    $post = Post::findBySlugWithHistory($slug);

    if (! $post) {
        abort(404);
    }

    // If the slug doesn't match the current one, redirect
    if ($post->slug !== $slug) {
        return redirect()->route('posts.show', $post->slug, 301);
    }

    return view('posts.show', compact('post'));
}
```

### Accessing slug history

You can access all previous slugs of a model via the `slugHistory` relationship:

```php
$post->slugHistory; // Collection of SlugHistory entries

$post->slugHistory->pluck('slug'); // ["old-slug", "older-slug"]

// Each entry is timestamped
$post->slugHistory->first()->created_at; // Carbon instance
```

## 🌍 Translatable Slugs

For multilingual applications, the optional `HasTranslatableSlug` trait integrates with [`spatie/laravel-translatable`](https://github.com/spatie/laravel-translatable) to generate one slug per locale (e.g. `/en/hello-world` and `/de/hallo-welt`).

### Setup

Install spatie/laravel-translatable:

```bash
composer require spatie/laravel-translatable
```

In your migration, define the source and slug columns as `json`:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->json('title')->nullable();
    $table->json('slug')->nullable();
});
```

### Usage

Use `HasTranslatableSlug` instead of `HasSlug` and add spatie's `HasTranslations` trait:

```php
use Oliwol\Slugify\HasTranslatableSlug;
use Oliwol\Slugify\Slugify;
use Spatie\Translatable\HasTranslations;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasTranslations, HasTranslatableSlug;

    public array $translatable = ['title', 'slug'];
}
```

### Generating slugs per locale

When the model is saved, a slug is generated for **each locale** that has a value in the source attribute:

```php
$post = Post::create([
    'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
]);

$post->getTranslation('slug', 'en'); // → 'hello-world'
$post->getTranslation('slug', 'de'); // → 'hallo-welt'
```

### Per-locale uniqueness

Uniqueness is checked **per locale** using JSON-path queries. Two models can share the same English slug as long as their other locales differ — but within a single locale, suffixes are appended:

```php
Post::create(['title' => ['en' => 'Hello', 'de' => 'Erster']]);
$second = Post::create(['title' => ['en' => 'Hello', 'de' => 'Zweiter']]);

$second->getTranslation('slug', 'en'); // → 'hello-2'  (incremented)
$second->getTranslation('slug', 'de'); // → 'zweiter'  (untouched)
```

### Finding models by translated slug

`findBySlug()` accepts an optional `$locale` parameter (defaults to `app()->getLocale()`):

```php
Post::findBySlug('hello-world', 'en'); // → Post
Post::findBySlug('hallo-welt', 'de');  // → same Post

// Without explicit locale, uses the current app locale:
app()->setLocale('de');
Post::findBySlug('hallo-welt'); // → Post
```

### Method sources

Method sources work too — the method is called once per locale with the locale context active:

```php
#[Slugify(from: 'getFullTitle', to: 'slug')]
class Post extends Model
{
    use HasTranslations, HasTranslatableSlug;

    public array $translatable = ['title', 'slug'];

    public function getFullTitle(): string
    {
        // $this->getLocale() reflects the current locale being generated.
        return 'post-' . $this->getTranslation('title', $this->getLocale(), false);
    }
}
```

> **Note**: When using a method source with `HasTranslatableSlug`, the available locales are gathered from all translatable attributes on the model (excluding the slug target). For attribute sources, locales come from the source attribute itself.

> **Limitation**: `HasTranslatableSlug` only supports a **single attribute name** or a **method name** as source. Array sources (e.g. `from: ['first_name', 'last_name']`) are not supported — use a method source instead to combine multiple translatable values.

## 📡 Events

The package dispatches events during the slug lifecycle, allowing you to hook in for logging, cache invalidation, or redirect management.

### Available events

| Event | Dispatched when | Properties |
|---|---|---|
| `Oliwol\Slugify\Events\SlugGenerated` | A slug is created for the first time | `$model`, `$slug` |
| `Oliwol\Slugify\Events\SlugUpdated` | An existing slug changes to a new value | `$model`, `$oldSlug`, `$newSlug` |

Events are **only** dispatched when the slug actually changes — if a save results in the same slug value, no event is fired.

### Listening to events

Register listeners in your `EventServiceProvider` or use closures:

```php
use Oliwol\Slugify\Events\SlugGenerated;
use Oliwol\Slugify\Events\SlugUpdated;

// In EventServiceProvider::$listen or via Event::listen()
Event::listen(SlugGenerated::class, function (SlugGenerated $event) {
    Log::info("Slug created: {$event->slug}", [
        'model' => get_class($event->model),
        'id' => $event->model->getKey(),
    ]);
});

Event::listen(SlugUpdated::class, function (SlugUpdated $event) {
    Log::info("Slug changed: {$event->oldSlug} → {$event->newSlug}", [
        'model' => get_class($event->model),
        'id' => $event->model->getKey(),
    ]);

    // Example: create a redirect entry
    Redirect::create([
        'from' => $event->oldSlug,
        'to' => $event->newSlug,
    ]);
});
```

### Combining with Slug History

When using both `HasSlugHistory` and events, the slug history is recorded automatically by the trait while events give you additional flexibility for custom logic. They work independently and can be used together or separately.

## 🔧 Artisan Command

The package provides an Artisan command to generate or regenerate slugs for existing database records — useful when adding the package to an existing project or after changing slug configuration.

### Generate missing slugs

```bash
php artisan slugify:generate "App\Models\Post"
```

This processes all records where the slug column is `null` or empty, generates a slug from the configured source attribute(s), and saves the result. Records that already have a slug are skipped.

### Overwrite existing slugs

Use `--force` to regenerate slugs for **all** records, including those that already have one:

```bash
php artisan slugify:generate "App\Models\Post" --force
```

### Preview changes

Use `--dry-run` to see how many slugs would be generated without actually saving anything:

```bash
php artisan slugify:generate "App\Models\Post" --dry-run
```

### Performance

The command processes records in chunks of 200 and displays a progress bar, making it safe to use on large datasets without running into memory issues.

## ✅ Validation

The package provides two complementary validation rules:

### Format: `SlugFormat`

Use `SlugFormat` to validate that a string is a proper slug — lowercase, alphanumeric, no leading/trailing/consecutive separators:

```php
use Oliwol\Slugify\Rules\SlugFormat;

'slug' => ['required', new SlugFormat()],

// Custom separator
'slug' => ['required', new SlugFormat(separator: '_')],
```

### Uniqueness: `SlugRule`

Use `SlugRule` to validate that a slug doesn't already exist in the database — respecting the configured slug column and scoping automatically:

```php
use Oliwol\Slugify\Rules\SlugRule;

// Basic — create scenario
public function rules(): array
{
    return [
        'slug' => ['required', 'string', new SlugRule(Post::class)],
    ];
}

// Update — ignore the current model so its own slug passes
public function rules(): array
{
    return [
        'slug' => ['required', 'string', SlugRule::for(Post::class)->ignore($this->post)],
    ];
}

// Scoped — additionally constrain by a column value
public function rules(): array
{
    return [
        'slug' => [
            'required',
            'string',
            SlugRule::for(Post::class)->scope('tenant_id', auth()->user()->tenant_id),
        ],
    ];
}
```

Both rules can be combined:

```php
'slug' => ['required', new SlugFormat(), new SlugRule(Post::class)],
```

`SlugRule` uses the model's configured slug column (`to` / `getAttributeToSaveSlugTo()`) and applies `scopeSlugQuery()` automatically. Use `->ignore($model)` for update scenarios and `->scope($column, $value)` for additional constraints.

## 🧪 Testing

When using model factories, slugs are generated automatically via the `saving` hook on `create()`. For `make()` — which does not persist the model — no slug is generated. Use the `withSlug()` macro to control slug generation explicitly in tests:

```php
// make() without withSlug — no slug
$post = Post::factory()->make(); // slug is null

// make() with withSlug — slug is generated
$post = Post::factory()->withSlug()->make(); // slug: "hello-world"

// Custom slug
$post = Post::factory()->withSlug('my-custom-slug')->make();

// Batch creation — slugs are unique
$posts = Post::factory()->count(3)->withSlug()->create();
// → "hello-world", "hello-world-2", "hello-world-3"
```

The macro is registered automatically via the service provider and works with any factory for a model that uses `HasSlug`.

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
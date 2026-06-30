# Features

## Attribute-Only Usage (no trait required)

For simpler models that only need automatic slug generation — without `findBySlug`, slug history, or translatable slugs — you can skip the `HasSlug` trait entirely:

```php
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    // No trait required
}
```

Publish the config and register your models in the `models` array:

```bash
php artisan vendor:publish --tag=slugify-config
```

```php
// config/slugify.php
'models' => [
    App\Models\Post::class,
],
```

The service provider registers a wildcard listener that fires on every `saving` event. It skips models that already use `HasSlug` (no double processing) and models not listed in `config('slugify.models')`.

::: tip When to use the trait instead
Use `HasSlug` when you need `findBySlug()`, `findBySlugOrFail()`, slug history (`HasSlugHistory`), or translatable slugs (`HasTranslatableSlug`). Attribute-only usage is best for simple models where only automatic slug generation is needed.
:::

## Multiple Source Fields

Generate slugs from multiple attributes by passing an array:

```php
#[Slugify(from: ['first_name', 'last_name'], to: 'slug')]
class Author extends Model
{
    use HasSlug;
}
```

Null or empty values are skipped:
- `first_name: "John"`, `last_name: null` → `"john"`
- `first_name: ""`, `last_name: "Doe"` → `"doe"`

## Max Length

Truncate slugs at word boundaries to fit a maximum length:

```php
#[Slugify(from: 'title', to: 'slug', maxLength: 15)]
```

| Input | Slug |
|---|---|
| `"Hello World Foo"` | `"hello-world-foo"` (15 chars, fits) |
| `"Hello World Foo Bar"` | `"hello-world-foo"` (truncated at word boundary) |

Uniqueness suffixes are also accounted for within the max length.

## Custom Separator

Use a different character to separate words:

```php
#[Slugify(from: 'title', to: 'slug', separator: '_')]
```

`"Hello World"` → `"hello_world"`

The separator is also used for uniqueness suffixes: `"hello_world_2"`.

## Regeneration Control

Prevent slugs from changing on update (useful for SEO):

```php
#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
```

- Slug is generated on creation
- Source attribute changes on update are ignored
- Manual slug changes are still respected

## Finding Models by Slug

The trait provides two static lookup methods:

```php
// Returns the model or null
$post = Post::findBySlug('hello-world');

// Returns the model or throws ModelNotFoundException
$post = Post::findBySlugOrFail('hello-world');
```

Both methods respect the configured slug column and apply `scopeSlugQuery()` for scoped lookups.

## Route Model Binding

Use `routeBinding: true` in the `#[Slugify]` attribute to automatically configure route model binding by the slug column — no need to manually override `getRouteKeyName()`:

```php
#[Slugify(from: 'title', to: 'slug', routeBinding: true)]
class Post extends Model
{
    use HasSlug;
}
```

Laravel will now resolve `{post}` route parameters by slug automatically:

```php
Route::get('/posts/{post}', PostController::class);
// GET /posts/hello-world → resolves Post where slug = 'hello-world'
```

Without `routeBinding: true`, route model binding uses the primary key by default.

## Slug History

Track previous slugs for SEO-friendly 301 redirects. First, publish and run the migration:

```bash
php artisan vendor:publish --tag=slugify-migrations
php artisan migrate
```

Then add the `HasSlugHistory` trait:

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\HasSlugHistory;

#[Slugify(from: 'title', to: 'slug')]
class Post extends Model
{
    use HasSlug, HasSlugHistory;
}
```

### Lookup by old slug

```php
// Checks current slug first, then history
$post = Post::findBySlugWithHistory('old-slug');
```

### Implementing 301 redirects

The easiest approach is the `slug.redirect` middleware. Add it to any route that uses `HasSlugHistory` — it automatically detects a stale slug in the URL and issues a redirect to the current one, preserving the query string:

```php
Route::get('/posts/{post:slug}', PostController::class)
    ->middleware(['slug.redirect']);
```

No controller code required. The redirect status defaults to `301` and is configurable via `config/slugify.php` (publish with `php artisan vendor:publish --tag=slugify-config`).

Alternatively, handle the redirect manually in the controller:

```php
public function show(string $slug)
{
    $post = Post::findBySlugWithHistory($slug);

    if (! $post) {
        abort(404);
    }

    if ($post->slug !== $slug) {
        return redirect()->route('posts.show', $post->slug, 301);
    }

    return view('posts.show', compact('post'));
}
```

### Accessing history

```php
$post->slugHistory;                    // Collection of SlugHistory entries
$post->slugHistory->pluck('slug');     // ["old-slug", "older-slug"]
$post->slugHistory->first()->created_at; // Carbon instance
```

## Translatable Slugs

For multilingual applications, integrate with [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable):

```bash
composer require spatie/laravel-translatable
```

Use `HasTranslatableSlug` instead of `HasSlug`:

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

```php
$post = Post::create([
    'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
]);

$post->getTranslation('slug', 'en'); // → 'hello-world'
$post->getTranslation('slug', 'de'); // → 'hallo-welt'

// Find by locale
Post::findBySlug('hallo-welt', 'de'); // → Post
```

Uniqueness is checked per locale. See the [API Reference](/api/reference#hastranslatableslug) for details.

::: warning Limitation
`HasTranslatableSlug` only supports a single attribute name or method name as source. Array sources are not supported — use a method source to combine multiple translatable values.
:::

## Events

The package dispatches events during the slug lifecycle:

| Event | When | Properties |
|---|---|---|
| `SlugGenerated` | Slug created for the first time | `$model`, `$slug` |
| `SlugUpdated` | Existing slug changes | `$model`, `$oldSlug`, `$newSlug` |

Events are only dispatched when the slug actually changes.

```php
use Oliwol\Slugify\Events\SlugGenerated;
use Oliwol\Slugify\Events\SlugUpdated;

Event::listen(SlugGenerated::class, function (SlugGenerated $event) {
    Log::info("Slug created: {$event->slug}");
});

Event::listen(SlugUpdated::class, function (SlugUpdated $event) {
    Redirect::create([
        'from' => $event->oldSlug,
        'to' => $event->newSlug,
    ]);
});
```

## Custom Scoping

Scope slug uniqueness per tenant, team, or any custom criteria:

```php
public function scopeSlugQuery($query)
{
    return $query->where('tenant_id', $this->tenant_id);
}
```

This appends a `WHERE tenant_id = ?` clause when checking for existing slugs and when using `findBySlug()`.

## Validation

The package provides two complementary validation rules:

- **`SlugFormat`** — checks that a string is a valid slug (format)
- **`SlugRule`** — checks that a slug doesn't already exist in the database (uniqueness)

### Format validation

Use `SlugFormat` to reject invalid slug strings before they reach the database:

```php
use Oliwol\Slugify\Rules\SlugFormat;

'slug' => ['required', new SlugFormat()],
```

`SlugFormat` validates that the value is lowercase, contains only alphanumeric characters and the separator, and has no leading, trailing, or consecutive separators. A custom separator can be passed:

```php
'slug' => ['required', new SlugFormat(separator: '_')],
```

### Uniqueness validation

Use `SlugRule` to validate uniqueness in form requests — respecting the configured slug column and scoping automatically.

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

See the [API Reference](/api/reference#slugformat) for full details.

## Testing with Factories

Slugs are generated automatically via the `saving` hook when using `create()` in factories. For `make()` — which does not persist the model — no slug is generated. Use the `withSlug()` macro when you need a slug on an unsaved model:

```php
// No slug on make() by default
$post = Post::factory()->make(); // slug: null

// Generate slug on make()
$post = Post::factory()->withSlug()->make(); // slug: "hello-world"

// Custom slug
$post = Post::factory()->withSlug('my-custom-slug')->make();

// Batch creation — slugs are unique
$posts = Post::factory()->count(3)->withSlug()->create();
// → "hello-world", "hello-world-2", "hello-world-3"
```

The macro is registered automatically and works with any factory for a model using `HasSlug` or with a `#[Slugify]` attribute.

## Artisan Command

Generate or regenerate slugs for existing database records:

```bash
# Generate slugs for records that don't have one
php artisan slugify:generate "App\Models\Post"

# Regenerate all slugs (overwrite existing)
php artisan slugify:generate "App\Models\Post" --force

# Preview changes without saving
php artisan slugify:generate "App\Models\Post" --dry-run
```

Records are processed in chunks of 200 with a progress bar, safe for large datasets.

The command works for both `HasSlug` models and attribute-only models with a `#[Slugify]` attribute — no trait required.

## Laravel Boost Skill

This package bundles a [Laravel Boost](https://laravel.com/docs/boost) skill (`slugify-development`) so AI agents know how to scaffold sluggable models using this package's conventions — `#[Slugify]`, the `SlugConfig` fluent API, `appendId`, slug history and more.

If your project uses Boost, the skill is discovered automatically; no extra setup is required in this package. Run `php artisan boost:install` (and select *Skills*), or `php artisan boost:update --discover` if Boost is already installed. Boost scans your installed packages, finds the skill and offers to install it. Projects that don't use Boost can ignore this entirely.

# API Reference

## `#[Slugify]` Attribute

```php
#[Slugify(
    from: 'title',            // string|array — required
    to: 'slug',               // ?string — default: getRouteKeyName()
    separator: '-',           // ?string — default: '-'
    maxLength: null,          // ?int — default: null (no limit)
    regenerateOnUpdate: true, // bool — default: true
    routeBinding: false,      // bool — default: false
)]
```

When `routeBinding: true` is set (and `to` is specified), `getRouteKeyName()` returns the slug column so Laravel resolves route parameters by slug automatically.

## HasSlug Trait

### Static Methods

#### `findBySlug(string $slug): ?static`

Find a model by its slug. Returns `null` if not found.

```php
$post = Post::findBySlug('hello-world');
```

#### `findBySlugOrFail(string $slug): static`

Find a model by its slug or throw `ModelNotFoundException`.

```php
$post = Post::findBySlugOrFail('hello-world');
```

### Instance Methods

#### `createSlug(): void`

Generate and set the slug on the model. Called automatically during the `saving` event.

#### `isSluggable(): bool`

Determine if the model can have a slug generated. Returns `false` if:
- The slug target column equals the primary key
- No source attribute has a filled string value

#### `slugify(string $toSlug): string`

Convert a string to a slug using the configured separator and language.

```php
$model->slugify('Hello World'); // "hello-world"
```

#### `incrementSlugIfExists(string $slug): string`

Ensure the slug is unique by appending an incrementing suffix if needed.

#### `truncateSlug(string $slug, int $reservedLength = 0): string`

Truncate the slug at word boundaries to fit within the max length.

#### `getRouteKeyName(): string`

Return the route key name. When `routeBinding: true` is configured and `to` is set, returns the slug column. Otherwise delegates to the model's primary key.

#### `shouldUseSlugForRouteBinding(): bool`

Return whether the model is configured to use the slug column for route model binding.

### Overridable Methods

#### `getAttributeToCreateSlugFrom(): string|array`

Return the source attribute name(s) or method name. Override to customize.

#### `getAttributeToSaveSlugTo(): string`

Return the column name where the slug is saved. Defaults to `getRouteKeyName()`.

#### `getSlugSeparator(): string`

Return the separator character. Default: `'-'`.

#### `getMaxSlugLength(): ?int`

Return the maximum slug length. Default: `null` (no limit).

#### `shouldRegenerateSlugOnUpdate(): bool`

Return whether the slug should regenerate on source attribute change. Default: `true`.

#### `shouldUseSlugForRouteBinding(): bool`

Return whether route model binding should use the slug column. Default: derived from `routeBinding` attribute parameter. Override to use dynamic logic.

```php
public function shouldUseSlugForRouteBinding(): bool
{
    return true;
}
```

#### `getSlugLanguage(): string`

Return the language for transliteration. Default: `'en'`.

#### `scopeSlugQuery($query)`

Apply custom scoping to uniqueness checks and `findBySlug()` queries.

```php
public function scopeSlugQuery($query)
{
    return $query->where('tenant_id', $this->tenant_id);
}
```

## HasSlugHistory Trait

Requires the `slug_history` table. Publish with:

```bash
php artisan vendor:publish --tag=slugify-migrations
```

### Static Methods

#### `findBySlugWithHistory(string $slug): ?static`

Find a model by its current slug or any historical slug.

### Relationships

#### `slugHistory(): MorphMany`

Access all previous slugs:

```php
$post->slugHistory; // Collection of SlugHistory entries
```

## HasTranslatableSlug Trait

Requires `spatie/laravel-translatable`.

### Static Methods

#### `findBySlug(string $slug, ?string $locale = null): ?static`

Find a model by its translated slug. Defaults to `app()->getLocale()`.

```php
Post::findBySlug('hallo-welt', 'de');
Post::findBySlug('hello-world'); // uses current app locale
```

## `withSlug()` Factory Macro

A macro registered on `Illuminate\Database\Eloquent\Factories\Factory` that controls slug generation in test factories.

```php
Factory::withSlug(?string $slug = null): static
```

Available on all factories for models that use `HasSlug`. Registered automatically by the service provider.

### Parameters

| Parameter | Type | Description |
|---|---|---|
| `$slug` | `?string` | Optional custom slug. If omitted, the slug is generated from the configured source attribute. |

### Examples

```php
// Unsaved model with auto-generated slug
Post::factory()->withSlug()->make();

// Unsaved model with custom slug
Post::factory()->withSlug('my-slug')->make();

// Batch creation with unique slugs
Post::factory()->count(3)->withSlug()->create();
// → "hello-world", "hello-world-2", "hello-world-3"
```

### Behaviour

- For `make()`: generates the slug immediately without saving
- For `create()`: lets the `saving` event handle uniqueness sequentially, producing correct incremented slugs in batch scenarios
- Models without `HasSlug` are silently skipped

## SlugRule

A validation rule for checking slug uniqueness in form requests.

```php
use Oliwol\Slugify\Rules\SlugRule;
```

### Constructor

#### `new SlugRule(string $modelClass)`

Create a new rule for the given model class.

```php
new SlugRule(Post::class)
```

### Static Methods

#### `SlugRule::for(string $modelClass): static`

Fluent alternative to the constructor.

```php
SlugRule::for(Post::class)
```

### Instance Methods

#### `ignore(Model $model): static`

Exclude a specific model from the uniqueness check. Use this for update scenarios so the model's own slug does not trigger a failure.

```php
SlugRule::for(Post::class)->ignore($this->post)
```

#### `scope(string $column, mixed $value): static`

Add an extra `WHERE` constraint to the uniqueness check. Chainable.

```php
SlugRule::for(Post::class)->scope('tenant_id', auth()->user()->tenant_id)
```

### Behaviour

- Uses the model's configured slug column (`to` / `getAttributeToSaveSlugTo()`)
- Calls `scopeSlugQuery()` automatically when the model defines it
- Provides a translatable error message via `slugify::validation.slug_unique`

### Examples

```php
// Create
'slug' => ['required', 'string', new SlugRule(Post::class)]

// Update
'slug' => ['required', 'string', SlugRule::for(Post::class)->ignore($this->post)]

// Scoped create
'slug' => ['required', 'string', SlugRule::for(Post::class)->scope('tenant_id', auth()->user()->tenant_id)]
```

## SlugFormat

A validation rule for checking that a value is a valid slug format.

```php
use Oliwol\Slugify\Rules\SlugFormat;
```

### Constructor

#### `new SlugFormat(string $separator = '-')`

Create a new format rule with the given separator (default: `-`).

### Behaviour

Validates that the value:
- Contains only lowercase letters (`a–z`), numbers (`0–9`), and the separator
- Does not start or end with the separator
- Does not contain consecutive separators

Fails for non-string values, empty strings, uppercase letters, or special characters.

### Examples

```php
// Default separator
'slug' => ['required', new SlugFormat()]

// Custom separator
'slug' => ['required', new SlugFormat(separator: '_')]

// Combined format + uniqueness check
'slug' => ['required', new SlugFormat(), new SlugRule(Post::class)]
```

## SlugRedirectMiddleware

Automatically redirects requests using a historical slug to the current canonical URL. Registered under the alias `slug.redirect`.

### Usage

```php
Route::get('/posts/{post:slug}', PostController::class)
    ->middleware(['slug.redirect']);
```

Requires `HasSlugHistory` on the bound model and `SubstituteBindings` in the middleware stack (included in Laravel's default `web` group).

### Behaviour

- Compares the URL slug against the model's current slug after route model binding
- If they differ, issues a redirect to the URL with the current slug
- Preserves the query string on redirect
- Passes through unchanged for models without `HasSlugHistory`

### Configuration

The redirect status code defaults to `301` and can be changed by publishing the config:

```bash
php artisan vendor:publish --tag=slugify-config
```

```php
// config/slugify.php
return [
    'redirect_status' => 301,
];
```

## Events

### `Oliwol\Slugify\Events\SlugGenerated`

Dispatched when a slug is created for the first time.

| Property | Type | Description |
|---|---|---|
| `$model` | `Model` | The Eloquent model |
| `$slug` | `string` | The generated slug |

### `Oliwol\Slugify\Events\SlugUpdated`

Dispatched when an existing slug changes to a new value.

| Property | Type | Description |
|---|---|---|
| `$model` | `Model` | The Eloquent model |
| `$oldSlug` | `string` | The previous slug |
| `$newSlug` | `string` | The new slug |

## Artisan Command

### `slugify:generate`

```bash
php artisan slugify:generate {model} [--force] [--dry-run]
```

| Argument/Option | Description |
|---|---|
| `model` | Fully qualified model class name |
| `--force` | Overwrite existing slugs |
| `--dry-run` | Preview changes without saving |

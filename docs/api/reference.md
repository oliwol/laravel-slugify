# API Reference

## `#[Slugify]` Attribute

```php
#[Slugify(
    from: 'title',           // string|array — required
    to: 'slug',              // ?string — default: getRouteKeyName()
    separator: '-',          // ?string — default: '-'
    maxLength: null,         // ?int — default: null (no limit)
    regenerateOnUpdate: true // bool — default: true
)]
```

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

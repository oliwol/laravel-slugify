# Configuration

## Via `#[Slugify]` Attribute

The `#[Slugify]` attribute accepts the following parameters:

| Parameter | Type | Default | Description |
|---|---|---|---|
| `from` | `string\|array` | *(required)* | Source attribute(s) or method name |
| `to` | `string\|null` | `getRouteKeyName()` | Column to save the slug to |
| `separator` | `string\|null` | `'-'` | Word separator character |
| `maxLength` | `int\|null` | `null` | Maximum slug length (truncates at word boundaries) |
| `regenerateOnUpdate` | `bool` | `true` | Whether to regenerate on source change |

### Examples

```php
// Full configuration
#[Slugify(from: 'name', to: 'slug')]
class Post extends Model
{
    use HasSlug;
}

// Only 'from' — slug column determined by getRouteKeyName()
#[Slugify(from: 'name')]
class Post extends Model
{
    use HasSlug;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

// Multiple source attributes
#[Slugify(from: ['first_name', 'last_name'], to: 'slug')]
class Author extends Model
{
    use HasSlug;
}
// first_name: "John", last_name: "Doe" → "john-doe"

// Custom separator
#[Slugify(from: 'title', to: 'slug', separator: '_')]
class Post extends Model
{
    use HasSlug;
}
// "Hello World" → "hello_world"

// Method source for complex slug generation
#[Slugify(from: 'getFullTitle', to: 'slug')]
class Post extends Model
{
    use HasSlug;

    public function getFullTitle(): string
    {
        return $this->category->name . ' ' . $this->title;
    }
}
// "Tech Laravel Tips" → "tech-laravel-tips"

// SEO-safe — slug only generated on creation
#[Slugify(from: 'title', to: 'slug', regenerateOnUpdate: false)]
class Post extends Model
{
    use HasSlug;
}
```

::: warning
The `to` parameter only controls where the slug is saved. For route model binding, you still need to override `getRouteKeyName()` separately on your model.
:::

## Via Method Overrides

Alternatively, configure slug generation by overriding methods:

| Method | Returns | Description |
|---|---|---|
| `getAttributeToCreateSlugFrom()` | `string\|array` | Source attribute(s) |
| `getRouteKeyName()` | `string` | Slug column for route binding |
| `getAttributeToSaveSlugTo()` | `string` | Column to save slug (if different from route key) |
| `getSlugSeparator()` | `string` | Separator character (default `'-'`) |
| `getMaxSlugLength()` | `?int` | Max length, truncated at word boundaries |
| `shouldRegenerateSlugOnUpdate()` | `bool` | Whether to regenerate on update (default `true`) |
| `getSlugLanguage()` | `string` | Language for transliteration (default `'en'`) |
| `scopeSlugQuery()` | `Builder` | Scope for uniqueness checks |

```php
use Oliwol\Slugify\HasSlug;

class Post extends Model
{
    use HasSlug;

    public function getAttributeToCreateSlugFrom(): string|array
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getAttributeToSaveSlugTo(): string
    {
        return 'slug';
    }

    public function scopeSlugQuery($query)
    {
        return $query->where('tenant_id', $this->tenant_id);
    }
}
```

## Via the fluent `SlugConfig` API

For configurations that need a closure or conditional logic — things a PHP attribute cannot express — return a `SlugConfig` from a `slugConfig()` method:

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
            ->regenerateOnUpdate(false);
    }
}
```

| Method | Argument | Description |
|---|---|---|
| `from()` | `string\|array\|Closure` | Source attribute(s), method name, or a closure receiving the model |
| `to()` | `string` | Column to save the slug to |
| `separator()` | `string` | Separator character (default `'-'`) |
| `maxLength()` | `int` | Max length, truncated at word boundaries |
| `regenerateOnUpdate()` | `bool` | Whether to regenerate on update (default `true`) |
| `routeBinding()` | `bool` | Use the slug column for route binding (requires `to()`) |
| `appendId()` | `bool` | Anchor the route key to the primary key (`{slug}-{id}`) — see [ID-Anchored Slugs](/guide/id-anchored-slugs) |

The `from()` closure receives the model instance and is the only way to combine related-model data or apply custom logic, since attributes cannot hold callables. As with method sources, a closure source skips dirty detection — the slug is regenerated on every save unless `regenerateOnUpdate(false)` is set.

::: tip When to use which
The `#[Slugify]` attribute handles most cases with zero boilerplate. Reach for `SlugConfig` only when you need a closure, conditional configuration, or a fluent chain.
:::

## Configuration Priority

When more than one configuration source is present, they are resolved in this order:

1. **Method overrides** (e.g. `getAttributeToCreateSlugFrom()`) — highest precedence
2. **`slugConfig()`** returning a `SlugConfig`
3. **`#[Slugify]`** attribute
4. Otherwise a `LogicException` is thrown

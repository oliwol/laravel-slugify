# ID-Anchored Slugs

ID-anchored slugs combine the slug with the model's primary key into a single route key (`/posts/hello-world-5`). The **ID** is what actually resolves the model, so the slug can change freely without ever breaking a link. When someone visits an old URL, a canonical redirect points them to the current one.

This is our take on the same problem Spatie solves with "Self-Healing URLs" — different name, same value.

## Enabling

Set `appendId: true` on the `#[Slugify]` attribute. The `HasSlug` trait is **required** (the feature overrides the route-key methods, so attribute-only models cannot use it):

```php
use Oliwol\Slugify\HasSlug;
use Oliwol\Slugify\Slugify;

#[Slugify(from: 'title', to: 'slug', appendId: true)]
class Post extends Model
{
    use HasSlug; // required for appendId
}
```

Or via the fluent [`SlugConfig`](/guide/configuration) API:

```php
use Oliwol\Slugify\SlugConfig;

public function slugConfig(): SlugConfig
{
    return SlugConfig::create()
        ->from('title')
        ->to('slug')
        ->appendId();
}
```

## How it works

- `getRouteKey()` returns `{slug}-{id}` (e.g. `hello-world-5`), so `route('posts.show', $post)` generates the anchored URL automatically.
- `resolveRouteBindingQuery()` extracts the trailing number as the primary key and resolves the model by ID — the slug part is ignored for resolution.
- The `slug.redirect` middleware compares the slug in the URL with the current route key and issues a canonical redirect when they differ.

```php
use Illuminate\Support\Facades\Route;

Route::get('/posts/{post}', fn (Post $post) => view('posts.show', compact('post')))
    ->middleware(['slug.redirect'])
    ->name('posts.show');
```

```
GET /posts/hello-world-5      → 200 OK
# title changed to "Updated Title":
GET /posts/hello-world-5      → 308 → /posts/updated-title-5
```

The redirect preserves the query string. Its status defaults to `308` (Permanent Redirect) and is configurable in `config/slugify.php`:

```php
'id_anchored_redirect_status' => 308,
```

## Clean slugs, no increment suffix

Because the primary key already makes every URL unique, the slug itself stays clean — no `-2` uniqueness suffix is appended even for duplicate titles:

```php
$a = Post::create(['title' => 'Same Title']); // slug: "same-title", URL: same-title-1
$b = Post::create(['title' => 'Same Title']); // slug: "same-title", URL: same-title-2
```

`maxLength` still applies to the slug part.

## Slugs ending in a number

If the slug ends in a digit, the route key simply has two trailing numbers — the **last** one is always the ID, which keeps resolution unambiguous:

```php
$post = Post::create(['title' => 'Hello World 2']); // id 7
$post->getRouteKey(); // "hello-world-2-7" → resolves to id 7
```

## ID-anchored slugs vs. slug history

Both solve "links shouldn't break when a slug changes". Pick based on your URL and maintenance needs:

| | `appendId: true` | [`HasSlugHistory`](/guide/features#slug-history) |
|---|---|---|
| URL shape | contains the ID (`hello-world-5`) | clean (`hello-world`) |
| Extra DB table | none | `slug_history` |
| Maintenance | zero | history rows accumulate |
| Audit trail | no | full history of past slugs |
| Redirect status | `308` (configurable) | `301` (configurable) |

Use **`appendId`** when you want zero-maintenance redirects without an extra table and don't mind the ID in the URL. Use **`HasSlugHistory`** when you need clean, ID-free URLs and a full audit trail.

The two features are orthogonal and can both be active on the same model.
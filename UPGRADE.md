# Upgrade Guide

## From v1.x to v2.0

**v2.0 is a feature release and is backward compatible with v1.x.** For the vast majority of applications, upgrading requires no code changes — just update the dependency:

```bash
composer require oliwol/laravel-slugify:^2.0
```

Your existing models, attributes, traits and slugs continue to work unchanged. Everything new in v2.0 is opt-in.

### Requirements

These are unchanged from late v1.x:

- **PHP 8.4+** (required since v1.0.0)
- **Laravel 11, 12, or 13** (Laravel 13 support was added; 11 and 12 remain supported)

If your app already runs on a supported v1.x release, it already meets the v2.0 requirements.

### What's new (all opt-in)

| Feature | Summary |
|---|---|
| [`SlugConfig` fluent API](https://oliwol.github.io/laravel-slugify/guide/configuration#via-the-fluent-slugconfig-api) | A `slugConfig()` method as an alternative to `#[Slugify]`, for closures and conditional logic the attribute can't express |
| [ID-anchored slugs (`appendId`)](https://oliwol.github.io/laravel-slugify/guide/id-anchored-slugs) | `#[Slugify(appendId: true)]` route keys become `{slug}-{id}` with `308` canonical redirects |
| [Laravel Boost skill](https://oliwol.github.io/laravel-slugify/guide/features#laravel-boost-skill) | Bundled `slugify-development` skill, auto-discovered by Boost |

None of these change the behavior of existing models — adopt them only if you want them.

### Things to be aware of

These are **not** breaking changes, but worth knowing if you have customized the trait:

- **`getAttributeToCreateSlugFrom()` return type** widened from `string|array` to `string|array|Closure`. If you override this method on your model with a `: string|array` return type, that stays valid (a narrower return type is allowed). The closure case only ever occurs when you opt into a `slugConfig()` closure source.
- **New trait methods `getRouteKey()` and `resolveRouteBindingQuery()`.** The trait now defines these. For models **without** `appendId`, they return exactly what Eloquent returned before, so behavior is unchanged. If your model already overrides either method, your class-level override still takes precedence over the trait.
- **New config key `id_anchored_redirect_status`** (default `308`). Only relevant if you use `appendId`. If you published `config/slugify.php` under v1, the missing key falls back to the default automatically — re-publishing is optional (see below).

### Migration checklist

1. **Update the dependency**
   ```bash
   composer require oliwol/laravel-slugify:^2.0
   ```
2. **Run your test suite.** No changes are expected for existing models.
3. **(Optional) Refresh the published config** if you want the new `id_anchored_redirect_status` key visible in `config/slugify.php`:
   ```bash
   php artisan vendor:publish --tag=slugify-config --force
   ```
   This is only needed if you use `appendId` and want to change its redirect status; otherwise the default applies.
4. **(Optional) Adopt new features** — `SlugConfig`, `appendId`, or the Boost skill, as needed.

### Migrating from Spatie?

If you are coming from `spatie/laravel-sluggable` (v3 or v4) rather than from v1.x of this package, see the [Migrating from Spatie](https://oliwol.github.io/laravel-slugify/guide/migrating-from-spatie) guide instead.
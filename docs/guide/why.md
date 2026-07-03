# Why Laravel Slugify?

Laravel Slugify generates clean, unique slugs for Eloquent models with as little ceremony as possible — a single PHP attribute, no boilerplate, sensible defaults.

It covers the same ground as [`spatie/laravel-sluggable`](https://github.com/spatie/laravel-sluggable), a mature and excellent package. This page is an honest look at what Laravel Slugify offers and where each package is stronger, so you can pick the right tool.

## Design principles

- **Attribute-first.** Most models need one line: `#[Slugify(from: 'title', to: 'slug')]`. The trait is optional — add it only when you need its query helpers or routing behavior.
- **Batteries included, opt-in.** Slug history, events, translatable slugs, ID-anchored URLs and a bulk Artisan command ship in the box, but nothing activates until you ask for it.
- **Rich attribute.** Separator, max length, regeneration control, route binding and ID-anchoring all live on the attribute itself — no fluent builder required for the common cases.

## What sets it apart

- **Slug history with timestamps** (`HasSlugHistory`) — a full audit trail of past slugs plus ready-made `301` redirects.
- **Lifecycle events** — `SlugGenerated` and `SlugUpdated` let you hook into slug changes (e.g. to record redirects or bust caches).
- **Artisan bulk command** — `slugify:generate` backfills or regenerates slugs across existing records, safely, in chunks.
- **A configuration-rich attribute** — options that other packages put behind a fluent builder are available inline.

## Honest comparison with Spatie v4

Since Spatie v4, the two packages overlap heavily — both have attribute-only configuration, a fluent config API, translatable slugs and self-healing / ID-anchored URLs.

| | Laravel Slugify v2 | Spatie v4 |
|---|---|---|
| Slug history with timestamps | ✅ | ❌ |
| Lifecycle events | ✅ | ❌ |
| Artisan bulk generation | ✅ | ❌ |
| Options on the attribute itself | ✅ (separator, maxLength, …) | ⚠️ `from` / `to` / `selfHealing` only |
| Overridable actions | ❌ | ✅ |
| Fluent config API | ✅ `SlugConfig` | ✅ `SlugOptions` |
| ID-anchored URLs | ✅ `appendId` | ✅ "Self-Healing" |

**Where Spatie v4 is stronger:** overridable actions — you can swap its slug generator or self-healing resolver for your own class via config. Laravel Slugify has no equivalent today; use a closure source or events instead.

If those specific trade-offs don't matter to you, both packages will serve you well. For a complete, side-by-side migration walkthrough, see [Migrating from Spatie](/guide/migrating-from-spatie).

## Next steps

- [Getting Started](/guide/getting-started) — install and create your first slug
- [Configuration](/guide/configuration) — the attribute and the `SlugConfig` API
- [Features](/guide/features) — history, events, translatable slugs, validation and more
---
layout: home

hero:
  name: Laravel Slugify
  text: Clean, automatic slugs for Eloquent
  tagline: Clean, automatic slugs from a single PHP attribute — the trait is optional, the defaults are sensible.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: Why Laravel Slugify?
      link: /guide/why
    - theme: alt
      text: View on GitHub
      link: https://github.com/oliwol/laravel-slugify

features:
  - icon: 🏷️
    title: "#[Slugify] Attribute"
    details: Configure slug generation with a single PHP attribute — no trait required. Reach for the fluent SlugConfig API when you need closures.
  - icon: 🔗
    title: ID-Anchored URLs
    details: Opt into appendId for self-healing /post-5 style URLs that survive slug changes with a 308 canonical redirect.
  - icon: 🔢
    title: Unique Slugs
    details: Automatic uniqueness with intelligent incrementing (my-post, my-post-2, my-post-3).
  - icon: 🏢
    title: Multi-Tenant Scoping
    details: Scope slug uniqueness per tenant, team, or any custom query — built right in.
  - icon: 📜
    title: Slug History
    details: Track previous slugs for SEO-friendly 301 redirects when slugs change.
  - icon: 📡
    title: Events
    details: Hook into the slug lifecycle with SlugGenerated and SlugUpdated events.
  - icon: 🔧
    title: Artisan Command
    details: Generate or regenerate slugs for existing records with a single command.
---

<?php

declare(strict_types=1);

return [

    /*
     * HTTP status code used by SlugRedirectMiddleware when redirecting
     * from a historical slug to the current one.
     *
     * 301 — Permanent redirect (recommended for SEO)
     * 302 — Temporary redirect
     */
    'redirect_status' => 301,

    /*
     * HTTP status code used for the canonical redirect when an ID-anchored slug
     * (#[Slugify(appendId: true)]) in the URL no longer matches the current slug.
     *
     * 308 — Permanent redirect that preserves the request method (recommended)
     * 301 — Permanent redirect
     */
    'id_anchored_redirect_status' => 308,

    /*
     * Models that should have slugs generated automatically without using the HasSlug trait.
     * Add the fully qualified class names of your Eloquent models here.
     * Each model must have a #[Slugify] attribute to configure slug generation.
     */
    'models' => [],

];

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
     * Models that should have slugs generated automatically without using the HasSlug trait.
     * Add the fully qualified class names of your Eloquent models here.
     * Each model must have a #[Slugify] attribute to configure slug generation.
     */
    'models' => [],

];

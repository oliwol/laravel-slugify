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

];

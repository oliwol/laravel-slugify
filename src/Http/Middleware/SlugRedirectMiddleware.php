<?php

declare(strict_types=1);

namespace Oliwol\Slugify\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Oliwol\Slugify\HasSlugHistory;
use Symfony\Component\HttpFoundation\Response;

final class SlugRedirectMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        foreach ($route->parameters() as $paramName => $model) {
            if (! $model instanceof Model) {
                continue;
            }

            // @phpstan-ignore-next-line cast.string
            $urlValue = (string) ($route->originalParameters()[$paramName] ?? '');

            if ($urlValue === '') {
                continue; // @codeCoverageIgnore
            }

            // ID-anchored slugs (#[Slugify(appendId: true)]): the route key is "{slug}-{id}".
            // When the slug part is stale, redirect to the canonical "{currentSlug}-{id}".
            if (method_exists($model, 'isIdAnchored') && $model->isIdAnchored()) {
                // @phpstan-ignore-next-line cast.string
                $canonical = (string) $model->getRouteKey();

                if ($urlValue === $canonical) {
                    continue;
                }

                $status = config('slugify.id_anchored_redirect_status', 308);
                $redirect = $this->buildRedirect($request, $urlValue, $canonical, is_int($status) ? $status : 308);

                if ($redirect instanceof RedirectResponse) {
                    return $redirect;
                }

                continue; // @codeCoverageIgnore
            }

            if (! in_array(HasSlugHistory::class, class_uses_recursive($model), true)) {
                continue;
            }

            /** @var string $slugColumn */
            $slugColumn = $model->getAttributeToSaveSlugTo(); // @phpstan-ignore method.notFound

            // @phpstan-ignore-next-line cast.string
            $currentSlug = (string) $model->getAttribute($slugColumn);

            if ($urlValue === $currentSlug) {
                continue;
            }

            $status = config('slugify.redirect_status', 301);
            $redirect = $this->buildRedirect($request, $urlValue, $currentSlug, is_int($status) ? $status : 301);

            if ($redirect instanceof RedirectResponse) {
                return $redirect;
            }
        }

        return $next($request);
    }

    private function buildRedirect(Request $request, string $from, string $to, int $status): ?RedirectResponse
    {
        $path = $request->getPathInfo();
        $newPath = preg_replace(
            '#(^|/)'.preg_quote($from, '#').'(/|$)#',
            '$1'.$to.'$2',
            $path
        ) ?? $path;

        if ($newPath === $path) {
            return null; // @codeCoverageIgnore
        }

        $query = $request->getQueryString();
        $redirectUrl = $newPath.($query !== null ? '?'.$query : '');

        return new RedirectResponse($redirectUrl, $status);
    }
}

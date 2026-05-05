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

            if (! in_array(HasSlugHistory::class, class_uses_recursive($model), true)) {
                continue;
            }

            /** @var string $slugColumn */
            $slugColumn = $model->getAttributeToSaveSlugTo(); // @phpstan-ignore method.notFound

            // @phpstan-ignore-next-line cast.string
            $currentSlug = (string) $model->getAttribute($slugColumn);
            // @phpstan-ignore-next-line cast.string
            $urlSlug = (string) ($route->originalParameters()[$paramName] ?? '');

            if ($urlSlug === '') {
                continue; // @codeCoverageIgnore
            }

            if ($urlSlug === $currentSlug) {
                continue;
            }

            $path = $request->getPathInfo();
            $newPath = preg_replace(
                '#(^|/)'.preg_quote($urlSlug, '#').'(/|$)#',
                '$1'.$currentSlug.'$2',
                $path
            ) ?? $path;

            if ($newPath === $path) {
                continue; // @codeCoverageIgnore
            }

            $query = $request->getQueryString();
            $redirectUrl = $newPath.($query !== null ? '?'.$query : '');

            $status = config('slugify.redirect_status', 301);

            return new RedirectResponse($redirectUrl, is_int($status) ? $status : 301);
        }

        return $next($request);
    }
}

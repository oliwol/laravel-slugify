<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Oliwol\Slugify\Console\SlugifyGenerateCommand;
use Oliwol\Slugify\Http\Middleware\SlugRedirectMiddleware;

final class SlugifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/slugify.php', 'slugify');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'slugify');

        $this->app->make(Router::class)->aliasMiddleware('slug.redirect', SlugRedirectMiddleware::class);

        Event::listen('eloquent.saving: *', function (string $event, array $payload): void {
            /** @var Model $model */
            $model = $payload[0];

            if (in_array(HasSlug::class, class_uses_recursive($model), true)) {
                return;
            }

            /** @var list<class-string> $models */
            $models = (array) config('slugify.models', []);

            if (! in_array($model::class, $models, true)) {
                return;
            }

            $creator = SlugCreator::tryForModel($model);

            if (! $creator instanceof SlugCreator) {
                return;
            }

            if (! $creator->isSluggable()) {
                return;
            }

            $creator->create();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                SlugifyGenerateCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/slugify.php' => config_path('slugify.php'),
            ], 'slugify-config');
        }

        Factory::macro('withSlug', function (?string $slug = null) {
            /** @var Factory<Model> $this */
            return $this->afterMaking(function (Model $model) use ($slug): void {
                if (in_array(HasSlug::class, class_uses_recursive($model), true)) {
                    /** @var string $slugColumn */
                    $slugColumn = $model->getAttributeToSaveSlugTo(); // @phpstan-ignore method.notFound

                    if ($slug !== null) {
                        $model->setAttribute($slugColumn, $slug);
                    } else {
                        $model->createSlug(); // @phpstan-ignore method.notFound
                        // Sync only the slug column so the saving event still fires and
                        // re-checks DB uniqueness sequentially during batch creates.
                        $model->syncOriginalAttribute($slugColumn);
                    }

                    return;
                }

                $creator = SlugCreator::tryForModel($model);

                if (! $creator instanceof SlugCreator) {
                    return;
                }

                $slugColumn = $creator->getTarget();

                if ($slug !== null) {
                    $model->setAttribute($slugColumn, $slug);
                } else {
                    $creator->create();
                    $model->syncOriginalAttribute($slugColumn);
                }
            });
        });

        $this->publishes([
            __DIR__.'/../database/migrations/create_slug_history_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_create_slug_history_table.php'
            ),
        ], 'slugify-migrations');
    }
}

<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Routing\Router;
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                SlugifyGenerateCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/slugify.php' => config_path('slugify.php'),
            ], 'slugify-config');
        }

        $this->publishes([
            __DIR__.'/../database/migrations/create_slug_history_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_create_slug_history_table.php'
            ),
        ], 'slugify-migrations');
    }
}

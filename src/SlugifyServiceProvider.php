<?php

declare(strict_types=1);

namespace Oliwol\Slugify;

use Illuminate\Support\ServiceProvider;

final class SlugifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/create_slug_history_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_create_slug_history_table.php'
            ),
        ], 'slugify-migrations');
    }
}

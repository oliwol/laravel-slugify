<?php

declare(strict_types=1);

use Oliwol\Slugify\SlugifyServiceProvider;

it('registers publishable migration', function (): void {
    $provider = new SlugifyServiceProvider($this->app);
    $provider->boot();

    $publishable = SlugifyServiceProvider::$publishGroups['slugify-migrations'] ?? [];

    expect($publishable)->not->toBeEmpty();
    expect(array_key_first($publishable))->toContain('create_slug_history_table.php.stub');
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Oliwol\Slugify\Events\SlugGenerated;
use Oliwol\Slugify\Events\SlugUpdated;
use Tests\Models\UserHasRouteKeyName;

it('dispatches SlugGenerated when a slug is created for the first time', function (): void {
    Event::fake([SlugGenerated::class, SlugUpdated::class]);

    UserHasRouteKeyName::create(['name' => 'John Doe']);

    Event::assertDispatched(SlugGenerated::class, fn (SlugGenerated $event): bool => $event->slug === 'john-doe'
        && $event->model instanceof UserHasRouteKeyName);

    Event::assertNotDispatched(SlugUpdated::class);
});

it('dispatches SlugUpdated when a slug changes', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    Event::fake([SlugGenerated::class, SlugUpdated::class]);

    $user->setAttribute('name', 'Jane Doe');
    $user->save();

    Event::assertDispatched(SlugUpdated::class, fn (SlugUpdated $event): bool => $event->oldSlug === 'john-doe'
        && $event->newSlug === 'jane-doe'
        && $event->model instanceof UserHasRouteKeyName);

    Event::assertNotDispatched(SlugGenerated::class);
});

it('does not dispatch events when slug does not change', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    Event::fake([SlugGenerated::class, SlugUpdated::class]);

    $user->setAttribute('email', 'john@example.com');
    $user->save();

    Event::assertNotDispatched(SlugGenerated::class);
    Event::assertNotDispatched(SlugUpdated::class);
});

it('does not dispatch SlugUpdated when slug regenerates to the same value', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    Event::fake([SlugGenerated::class, SlugUpdated::class]);

    // Change name and change it back — slug resolves to same value.
    $user->setAttribute('name', 'John Doe');
    $user->save();

    Event::assertNotDispatched(SlugUpdated::class);
});

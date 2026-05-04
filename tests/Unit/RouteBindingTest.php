<?php

declare(strict_types=1);

use Tests\Models\UserHasRouteKeyName;
use Tests\Models\UserWithAttribute;
use Tests\Models\UserWithRouteBinding;
use Tests\Models\UserWithRouteBindingNoTo;

it('returns the slug column as route key when routeBinding is enabled', function (): void {
    $user = new UserWithRouteBinding;

    expect($user->getRouteKeyName())->toBe('slug');
});

it('returns the primary key as route key when routeBinding is disabled', function (): void {
    $user = new UserWithAttribute;

    expect($user->getRouteKeyName())->toBe('id');
});

it('returns the primary key as route key when routeBinding is true but to is not set', function (): void {
    $user = new UserWithRouteBindingNoTo;

    expect($user->getRouteKeyName())->toBe('id');
});

it('shouldUseSlugForRouteBinding returns true when routeBinding is enabled with an explicit to column', function (): void {
    $user = new UserWithRouteBinding;

    expect($user->shouldUseSlugForRouteBinding())->toBeTrue();
});

it('shouldUseSlugForRouteBinding returns false when routeBinding is disabled', function (): void {
    $user = new UserWithAttribute;

    expect($user->shouldUseSlugForRouteBinding())->toBeFalse();
});

it('shouldUseSlugForRouteBinding returns false when routeBinding is true but to is not set', function (): void {
    $user = new UserWithRouteBindingNoTo;

    expect($user->shouldUseSlugForRouteBinding())->toBeFalse();
});

it('explicit getRouteKeyName override on the model takes precedence over the trait', function (): void {
    $user = new UserHasRouteKeyName;

    expect($user->getRouteKeyName())->toBe('slug');
});

it('slug is generated and findBySlug works with routeBinding enabled', function (): void {
    $user = UserWithRouteBinding::create(['name' => 'John Doe']);

    expect($user->slug)->toBe('john-doe')
        ->and(UserWithRouteBinding::findBySlug('john-doe'))->not->toBeNull();
});

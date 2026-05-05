<?php

declare(strict_types=1);

use Tests\Factories\UserNoSlugFactory;
use Tests\Factories\UserWithAttributeFactory;

it('make() without withSlug does not generate a slug', function (): void {
    $user = UserWithAttributeFactory::new()->make();

    expect($user->slug)->toBeNull();
});

it('make() with withSlug generates a slug', function (): void {
    $user = UserWithAttributeFactory::new()->withSlug()->make();

    expect($user->slug)->toBe('john-doe');
});

it('create() with withSlug generates a slug', function (): void {
    $user = UserWithAttributeFactory::new()->withSlug()->create();

    expect($user->fresh()->slug)->toBe('john-doe');
});

it('withSlug accepts a custom slug', function (): void {
    $user = UserWithAttributeFactory::new()->withSlug('my-custom-slug')->make();

    expect($user->slug)->toBe('my-custom-slug');
});

it('does nothing for models without HasSlug', function (): void {
    $user = UserNoSlugFactory::new()->withSlug()->make();

    expect($user->slug)->toBeNull();
});

it('generates unique slugs for batch creation', function (): void {
    $users = UserWithAttributeFactory::new()->count(3)->withSlug()->create();

    expect($users->pluck('slug')->all())->toBe(['john-doe', 'john-doe-2', 'john-doe-3']);
});

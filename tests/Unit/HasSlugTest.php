<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Tests\Models\Post;
use Tests\Models\UserHasRouteKeyName;
use Tests\Models\UserHasScope;
use Tests\Models\UserWithoutRouteKeyName;

it('creates a slug from an attribute', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe');
});

it('throws an exception when no slug field exists in database', function (): void {
    Post::create(['title' => 'My First Post']);
})->throws(QueryException::class);

test('not sluggable when getRouteKeyName is not set', function (): void {
    $user = UserWithoutRouteKeyName::create(['name' => 'John Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBeNull();
});

it('does not slugify when attribute is not dirty', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);
    $slug = $user->slug;

    $user->setAttribute('email', 'test@example.com');
    $user->save();

    expect($user->fresh()->slug)->toBe($slug);
});

it('does not slugify when slug is already filled', function (): void {
    $user = UserHasRouteKeyName::create([
        'name' => 'Jane Doe',
        'slug' => 'custom-slug',
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBe('custom-slug');
});

it('does not slugify when attribute to create slug from is null', function (): void {
    $user = UserHasRouteKeyName::create([
        'name' => null,
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBeNull();
});

it('does not slugify when attribute to create slug from is empty', function (): void {
    $user = UserHasRouteKeyName::create([
        'name' => '',
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBeNull();
});

it('does not slugify when attribute to create slug from not set', function (): void {
    $user = UserHasRouteKeyName::create();

    expect($user->fresh()->getAttribute('slug'))->toBeNull();
});

it('increments the slug when already used', function (): void {
    UserHasRouteKeyName::create([
        'name' => 'John Doe',
    ]);

    $user = UserHasRouteKeyName::create([
        'name' => 'John Doe',
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe-2');
});

it('increments the slug correctly multiple times', function (): void {
    UserHasRouteKeyName::create(['name' => 'John Doe']);
    UserHasRouteKeyName::create(['name' => 'John Doe']);
    UserHasRouteKeyName::create(['name' => 'John Doe']);

    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe-4');
});

it('incrementing respects scope', function (): void {
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 1]);
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 2]);
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 1]);

    $user = UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 2]);
    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe-2');
});

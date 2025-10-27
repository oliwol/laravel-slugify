<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

it('creates a slug from an attribute', function (): void {
    $user = new UserWithRouteKeyName();
    $user->name = 'John Doe';
    $user->save();

    expect($user->getAttribute('slug'))->toBe('john-doe');
});

test('not sluggable when getRouteKeyName is not set', function (): void {
    $user = new UserWithoutRouteKeyName();
    $user->name = 'John Doe';
    $user->save();

    expect($user->getAttribute('slug'))->toBeNull();
});

it('does not slugify when attribute is not dirty', function (): void {
    $user = UserWithRouteKeyName::create(['name' => 'John Doe']);
    $slug = $user->slug;

    $user->setAttribute('email', 'test@example.com');
    $user->save();

    expect($user->fresh()->slug)->toBe($slug);
});

it('does not slugify when slug is already filled', function (): void {
    $user = new UserWithRouteKeyName();
    $user->name = 'John Doe';
    $user->slug = 'custom-slug';
    $user->save();

    expect($user->getAttribute('slug'))->toBe('custom-slug');
});

it('increments the slug when already used', function (): void {
    UserWithRouteKeyName::create([
        'name' => 'John Doe',
    ]);

    $user = new UserWithRouteKeyName();
    $user->name = 'John Doe';
    $user->save();

    expect($user->getAttribute('slug'))->toBe('john-doe-2');
});

abstract class User extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];
}

final class UserWithoutRouteKeyName extends User
{
    public function getSlugKeyName(): string
    {
        return 'name';
    }
}

final class UserWithRouteKeyName extends User
{
    public function getSlugKeyName(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

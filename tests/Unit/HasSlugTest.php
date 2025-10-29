<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Oliwol\Slugify\HasSlug;

it('creates a slug from an attribute', function (): void {
    $user = UserWithRouteKeyName::create(['name' => 'John Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe');
});

it('throws an exception when no slug field exists', function (): void {
    Post::create(['title' => 'My First Post']);
})->throws(QueryException::class);

test('not sluggable when getRouteKeyName is not set', function (): void {
    $user = UserWithoutRouteKeyName::create(['name' => 'John Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBeNull();
});

it('does not slugify when attribute is not dirty', function (): void {
    $user = UserWithRouteKeyName::create(['name' => 'John Doe']);
    $slug = $user->slug;

    $user->setAttribute('email', 'test@example.com');
    $user->save();

    expect($user->fresh()->slug)->toBe($slug);
});

it('does not slugify when slug is already filled', function (): void {
    $user = UserWithRouteKeyName::create([
        'name' => 'Jane Doe',
        'slug' => 'custom-slug',
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBe('custom-slug');
});

it('increments the slug when already used', function (): void {
    UserWithRouteKeyName::create([
        'name' => 'John Doe',
    ]);

    $user = UserWithRouteKeyName::create([
        'name' => 'John Doe',
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe-2');
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
    public function getSlugifyKeyName(): string
    {
        return 'name';
    }
}

final class UserWithRouteKeyName extends User
{
    public function getSlugifyKeyName(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

final class Post extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'posts';

    protected $guarded = [];

    public function getSlugifyKeyName(): string
    {
        return 'title';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

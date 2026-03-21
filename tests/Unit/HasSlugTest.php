<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Tests\Models\AuthorWithAttribute;
use Tests\Models\AuthorWithMethod;
use Tests\Models\Post;
use Tests\Models\PostWithCustomSeparator;
use Tests\Models\UserHasRouteKeyName;
use Tests\Models\UserHasScope;
use Tests\Models\UserWithAttribute;
use Tests\Models\UserWithAttributeFromOnly;
use Tests\Models\UserWithoutAttributeOrMethod;
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

it('creates a slug using the #[Slugify] attribute with from and to', function (): void {
    $user = UserWithAttribute::create(['name' => 'Jane Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBe('jane-doe');
});

it('creates a slug using the #[Slugify] attribute with only from, falling back to getRouteKeyName', function (): void {
    $user = UserWithAttributeFromOnly::create(['name' => 'Jane Doe']);

    expect($user->fresh()->getAttribute('slug'))->toBe('jane-doe');
});

it('throws a LogicException when neither #[Slugify] attribute nor method override is present', function (): void {
    UserWithoutAttributeOrMethod::create(['name' => 'Jane Doe']);
})->throws(LogicException::class);

// --- Multiple source attributes ---

it('creates a slug from multiple attributes via #[Slugify] attribute', function (): void {
    $author = AuthorWithAttribute::create(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($author->fresh()->getAttribute('slug'))->toBe('john-doe');
});

it('creates a slug from multiple attributes via method override', function (): void {
    $author = AuthorWithMethod::create(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($author->fresh()->getAttribute('slug'))->toBe('john-doe');
});

it('skips null attributes when creating slug from multiple fields', function (): void {
    $author = AuthorWithAttribute::create(['first_name' => 'John', 'last_name' => null]);

    expect($author->fresh()->getAttribute('slug'))->toBe('john');
});

it('skips empty attributes when creating slug from multiple fields', function (): void {
    $author = AuthorWithAttribute::create(['first_name' => '', 'last_name' => 'Doe']);

    expect($author->fresh()->getAttribute('slug'))->toBe('doe');
});

it('is not sluggable when all source attributes are null', function (): void {
    $author = AuthorWithAttribute::create(['first_name' => null, 'last_name' => null]);

    expect($author->fresh()->getAttribute('slug'))->toBeNull();
});

it('regenerates slug when any source attribute is dirty', function (): void {
    $author = AuthorWithAttribute::create(['first_name' => 'John', 'last_name' => 'Doe']);

    $author->setAttribute('last_name', 'Smith');
    $author->save();

    expect($author->fresh()->getAttribute('slug'))->toBe('john-smith');
});

it('does not regenerate slug when no source attribute is dirty for multiple fields', function (): void {
    $author = AuthorWithAttribute::create(['first_name' => 'John', 'last_name' => 'Doe']);
    $slug = $author->slug;

    $author->setAttribute('email', 'john@example.com');
    $author->save();

    expect($author->fresh()->getAttribute('slug'))->toBe($slug);
});

it('increments slug from multiple attributes when already used', function (): void {
    AuthorWithAttribute::create(['first_name' => 'John', 'last_name' => 'Doe']);
    $author = AuthorWithAttribute::create(['first_name' => 'John', 'last_name' => 'Doe']);

    expect($author->fresh()->getAttribute('slug'))->toBe('john-doe-2');
});

// --- Custom separator ---

it('creates a slug with a custom separator via #[Slugify] attribute', function (): void {
    $post = PostWithCustomSeparator::create(['title' => 'Hello World']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello_world');
});

it('uses the custom separator for uniqueness suffix', function (): void {
    PostWithCustomSeparator::create(['title' => 'Hello World']);
    $post = PostWithCustomSeparator::create(['title' => 'Hello World']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello_world_2');
});

it('uses the default separator when none is specified', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'Hello World']);

    expect($user->fresh()->getAttribute('slug'))->toBe('hello-world');
});

<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Tests\Models\AuthorWithAttribute;
use Tests\Models\AuthorWithMethod;
use Tests\Models\Post;
use Tests\Models\PostNoRegenerate;
use Tests\Models\PostNoRegenerateMethod;
use Tests\Models\PostWithCustomSeparator;
use Tests\Models\PostWithMaxLength;
use Tests\Models\PostWithMaxLengthMethod;
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

// --- Max length ---

it('truncates slug at word boundary when exceeding max length', function (): void {
    // "hello-world-foo" = 15 chars, fits exactly
    $post = PostWithMaxLength::create(['title' => 'Hello World Foo']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello-world-foo');
});

it('truncates slug at word boundary when too long', function (): void {
    // "hello-world-foo-bar" = 19 chars, exceeds 15 → truncate to "hello-world-foo"
    $post = PostWithMaxLength::create(['title' => 'Hello World Foo Bar']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello-world-foo');
});

it('accounts for uniqueness suffix within max length', function (): void {
    // Both get "hello-world-foo" (15 chars), second needs suffix
    // "hello-world-foo" + "-2" = 17 > 15, so base truncated to "hello-world" + "-2" = 13
    PostWithMaxLength::create(['title' => 'Hello World Foo Bar']);
    $post = PostWithMaxLength::create(['title' => 'Hello World Foo Bar']);

    $slug = $post->fresh()->getAttribute('slug');
    expect($slug)->toBe('hello-world-2');
    expect(mb_strlen((string) $slug))->toBeLessThanOrEqual(15);
});

it('truncates slug via getMaxSlugLength method override', function (): void {
    // maxLength: 10 via method, "hello-world" = 11 chars → truncate to "hello"
    $post = PostWithMaxLengthMethod::create(['title' => 'Hello World']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello');
});

it('does not truncate when max length is null', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'This Is A Really Long Name That Should Not Be Truncated']);

    expect($user->fresh()->getAttribute('slug'))->toBe('this-is-a-really-long-name-that-should-not-be-truncated');
});

it('truncates single word without separator when exceeding max length', function (): void {
    // "abcdefghijklmnop" = 16 chars, no separator → hard truncate to 15
    $post = PostWithMaxLength::create(['title' => 'Abcdefghijklmnop']);

    expect($post->fresh()->getAttribute('slug'))->toBe('abcdefghijklmno');
});

it('handles truncation when single word exceeds remaining length', function (): void {
    // "ab-cdefghijklmnop" = 17 chars, exceeds 15 → truncated to "ab-cdefghijklmn" (15), next char is "o" (not separator) → trim to "ab"
    $post = PostWithMaxLength::create(['title' => 'Ab Cdefghijklmnop']);

    expect($post->fresh()->getAttribute('slug'))->toBe('ab');
    expect(mb_strlen((string) $post->fresh()->getAttribute('slug')))->toBeLessThanOrEqual(15);
});

// --- Regenerate on update ---

it('generates slug on creation when regenerateOnUpdate is false', function (): void {
    $post = PostNoRegenerate::create(['title' => 'Hello World']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello-world');
});

it('does not regenerate slug on update when regenerateOnUpdate is false', function (): void {
    $post = PostNoRegenerate::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'Changed Title');
    $post->save();

    expect($post->fresh()->getAttribute('slug'))->toBe('hello-world');
});

it('still regenerates slug on update when regenerateOnUpdate is true (default)', function (): void {
    $post = PostWithCustomSeparator::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'Changed Title');
    $post->save();

    expect($post->fresh()->getAttribute('slug'))->toBe('changed_title');
});

it('regenerates slug on update by default when using method override', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    $user->setAttribute('name', 'Jane Doe');
    $user->save();

    expect($user->fresh()->getAttribute('slug'))->toBe('jane-doe');
});

it('respects manual slug changes even when regenerateOnUpdate is false', function (): void {
    $post = PostNoRegenerate::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'Changed Title');
    $post->setAttribute('slug', 'custom-slug');
    $post->save();

    expect($post->fresh()->getAttribute('slug'))->toBe('custom-slug');
});

it('does not regenerate slug on update when shouldRegenerateSlugOnUpdate returns false via method override', function (): void {
    $post = PostNoRegenerateMethod::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'Changed Title');
    $post->save();

    expect($post->fresh()->getAttribute('slug'))->toBe('hello-world');
});

// --- findBySlug / findBySlugOrFail ---

it('finds a model by slug', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    $found = UserHasRouteKeyName::findBySlug('john-doe');

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($user->getKey());
});

it('returns null when slug is not found', function (): void {
    expect(UserHasRouteKeyName::findBySlug('nonexistent'))->toBeNull();
});

it('finds a model by slug or fails', function (): void {
    $user = UserHasRouteKeyName::create(['name' => 'John Doe']);

    $found = UserHasRouteKeyName::findBySlugOrFail('john-doe');

    expect($found->getKey())->toBe($user->getKey());
});

it('throws ModelNotFoundException when slug is not found', function (): void {
    UserHasRouteKeyName::findBySlugOrFail('nonexistent');
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('findBySlug respects the configured slug column', function (): void {
    $user = UserWithAttribute::create(['name' => 'Jane Doe']);

    $found = UserWithAttribute::findBySlug('jane-doe');

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($user->getKey());
});

it('findBySlug applies slug query scope', function (): void {
    // Scope filters by tenant_id — on a fresh instance tenant_id is null,
    // so only records with tenant_id = null are found.
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 1]);
    $unscoped = UserHasScope::create(['name' => 'Jane Doe', 'tenant_id' => null]);

    $found = UserHasScope::findBySlug('jane-doe');

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($unscoped->getKey());

    // Record with tenant_id = 1 is not found due to scope
    expect(UserHasScope::findBySlug('john-doe'))->toBeNull();
});

it('findBySlugOrFail applies slug query scope', function (): void {
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 1]);

    // Record exists but scope filters it out — should throw
    UserHasScope::findBySlugOrFail('john-doe');
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

<?php

declare(strict_types=1);

use Oliwol\Slugify\SlugCreator;
use Oliwol\Slugify\Slugify;
use Tests\Models\PostAttributeOnly;
use Tests\Models\PostAttributeOnlyNoRegenerate;
use Tests\Models\PostAttributeOnlyWithMethodSource;
use Tests\Models\PostAttributeOnlyWithScope;
use Tests\Models\UserWithAttribute;

// --- getSources ---

it('getSources returns the configured source field', function (): void {
    $creator = SlugCreator::tryForModel(new PostAttributeOnly(['title' => 'Test']));

    expect($creator->getSources())->toBe('title');
});

// --- isSluggable ---

it('isSluggable returns false when target equals the primary key', function (): void {
    // Slugify without 'to' → getTarget() uses getRouteKeyName() → 'id' (= keyName)
    $creator = new SlugCreator(UserWithAttribute::make(['name' => 'John']), new Slugify(from: 'name'));

    expect($creator->isSluggable())->toBeFalse();
});

it('isSluggable returns true for method source with a non-empty return value', function (): void {
    $creator = SlugCreator::tryForModel(PostAttributeOnlyWithMethodSource::make(['title' => 'Hello']));

    expect($creator->isSluggable())->toBeTrue();
});

it('isSluggable returns false for method source when return value is empty', function (): void {
    $creator = SlugCreator::tryForModel(PostAttributeOnlyWithMethodSource::make(['title' => null]));

    expect($creator->isSluggable())->toBeFalse();
});

it('isSluggable skips non-attribute fields and still finds a valid source', function (): void {
    $post = PostAttributeOnly::make(['title' => 'Test']);
    // 'nonexistent' is not a model attribute → hasAttribute returns false → continue
    $creator = new SlugCreator($post, new Slugify(from: ['nonexistent', 'title'], to: 'slug'));

    expect($creator->isSluggable())->toBeTrue();
});

// --- create(): early-return branches ---

it('create does nothing when none of the source attributes are dirty', function (): void {
    $post = PostAttributeOnly::make(['title' => 'Hello World', 'slug' => 'hello-world']);
    $post->syncOriginal(); // mark as "clean" – no dirty attributes

    SlugCreator::tryForModel($post)->create();

    expect($post->slug)->toBe('hello-world'); // unchanged
});

it('create preserves a manually set slug when both source and target are dirty', function (): void {
    $post = PostAttributeOnly::make(['title' => 'Original', 'slug' => 'original-slug']);
    $post->syncOriginal();

    $post->title = 'New Title';   // source dirty
    $post->slug = 'custom-slug';  // target dirty → manual override

    SlugCreator::tryForModel($post)->create();

    expect($post->slug)->toBe('custom-slug');
});

it('create skips slug regeneration when regenerateOnUpdate is false', function (): void {
    $post = PostAttributeOnlyNoRegenerate::make(['title' => 'Original', 'slug' => 'original-slug']);
    $post->syncOriginal();

    $post->title = 'New Title'; // source dirty, but target is not dirty

    SlugCreator::tryForModel($post)->create();

    expect($post->slug)->toBe('original-slug'); // not regenerated
});

it('create generates a slug from a method source', function (): void {
    $post = PostAttributeOnlyWithMethodSource::make(['title' => 'Hello World']);
    // new model → isDirty(['slugSource']) is irrelevant; usesMethod=true bypasses dirty check

    SlugCreator::tryForModel($post)->create();

    expect($post->slug)->toBe('hello-world');
});

// --- getSourceValue ---

it('getSourceValue returns value from a method source', function (): void {
    $creator = SlugCreator::tryForModel(PostAttributeOnlyWithMethodSource::make(['title' => 'Hello World']));

    expect($creator->getSourceValue())->toBe('Hello World');
});

it('getSourceValue returns null when method source returns an empty string', function (): void {
    $creator = SlugCreator::tryForModel(PostAttributeOnlyWithMethodSource::make(['title' => null]));

    expect($creator->getSourceValue())->toBeNull();
});

// --- applyScopeIfAvailable ---

it('calls scopeSlugQuery when the model defines it', function (): void {
    // create() → generate() → incrementIfExists() → slugExists() → applyScopeIfAvailable()
    $post = PostAttributeOnlyWithScope::make(['title' => 'Hello']);

    SlugCreator::tryForModel($post)->create();

    expect($post->slug)->toBe('hello');
});

// --- truncate ---

it('truncate returns slug unchanged when it fits within maxLength', function (): void {
    $creator = new SlugCreator(PostAttributeOnly::make(), new Slugify(from: 'title', to: 'slug', maxLength: 20));

    expect($creator->generate('hello'))->toBe('hello');
});

it('truncate returns clean slug when cut falls exactly on a separator', function (): void {
    // "hello world" → "hello-world" (11 chars), maxLength 5
    // truncated = "hello", nextChar = "-" → nextChar === separator → return "hello"
    $creator = new SlugCreator(PostAttributeOnly::make(), new Slugify(from: 'title', to: 'slug', maxLength: 5));

    expect($creator->generate('hello world'))->toBe('hello');
});

it('truncate trims at last separator when cut falls mid-word', function (): void {
    // "hello world" → "hello-world", maxLength 8
    // truncated = "hello-wo", nextChar = "r" → lastSep at 5 → return "hello"
    $creator = new SlugCreator(PostAttributeOnly::make(), new Slugify(from: 'title', to: 'slug', maxLength: 8));

    expect($creator->generate('hello world'))->toBe('hello');
});

it('truncate hard-cuts slug when no separator is present', function (): void {
    // "helloworld" → "helloworld", maxLength 4
    // truncated = "hell", nextChar = "o", no separator in "hell" → return "hell"
    $creator = new SlugCreator(PostAttributeOnly::make(), new Slugify(from: 'title', to: 'slug', maxLength: 4));

    expect($creator->generate('helloworld'))->toBe('hell');
});

// --- command with method source ---

it('command generates slugs for attribute-only models with a method source', function (): void {
    Illuminate\Support\Facades\DB::table('posts_with_slug')->insert(['title' => 'Hello World', 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => PostAttributeOnlyWithMethodSource::class])
        ->assertSuccessful();

    expect(PostAttributeOnlyWithMethodSource::first()->slug)->toBe('hello-world');
});

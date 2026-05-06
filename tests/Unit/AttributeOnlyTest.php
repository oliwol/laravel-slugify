<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tests\Factories\PostAttributeOnlyFactory;
use Tests\Models\PostAttributeOnly;
use Tests\Models\UserNoSlug;
use Tests\Models\UserWithAttribute;

beforeEach(function (): void {
    config(['slugify.models' => [PostAttributeOnly::class]]);
});

// --- Automatic slug generation via wildcard listener ---

it('generates a slug on create for attribute-only model', function (): void {
    $post = PostAttributeOnly::create(['title' => 'Hello World']);

    expect($post->slug)->toBe('hello-world');
});

it('does not generate a slug when model is not in config', function (): void {
    config(['slugify.models' => []]);

    $post = PostAttributeOnly::create(['title' => 'Hello World']);

    expect($post->slug)->toBeNull();
});

it('does not generate a slug when source attribute is empty', function (): void {
    $post = PostAttributeOnly::create(['title' => null]);

    expect($post->slug)->toBeNull();
});

it('does not generate a slug when config contains a model without #[Slugify] attribute', function (): void {
    config(['slugify.models' => [UserNoSlug::class]]);

    $user = UserNoSlug::create(['name' => 'John Doe']);

    expect($user->slug)->toBeNull();
});

it('regenerates a slug on update', function (): void {
    $post = PostAttributeOnly::create(['title' => 'Original Title']);
    expect($post->slug)->toBe('original-title');

    $post->update(['title' => 'Updated Title']);

    expect($post->fresh()->slug)->toBe('updated-title');
});

it('increments slug when it already exists', function (): void {
    PostAttributeOnly::create(['title' => 'Hello World']);
    $second = PostAttributeOnly::create(['title' => 'Hello World']);

    expect($second->slug)->toBe('hello-world-2');
});

it('does not affect HasSlug models saved when listener fires', function (): void {
    config(['slugify.models' => [UserWithAttribute::class]]);

    $user = UserWithAttribute::create(['name' => 'John Doe']);

    // HasSlug handles its own slug — listener skips it. Slug should still be generated.
    expect($user->slug)->toBe('john-doe');
});

// --- withSlug macro for attribute-only models ---

it('withSlug macro auto-generates a slug for attribute-only model', function (): void {
    $post = PostAttributeOnlyFactory::new()->withSlug()->make();

    expect($post->slug)->toBe('hello-world');
});

it('withSlug macro accepts a custom slug for attribute-only model', function (): void {
    $post = PostAttributeOnlyFactory::new()->withSlug('my-custom-slug')->make();

    expect($post->slug)->toBe('my-custom-slug');
});

it('withSlug macro creates unique slugs in batch for attribute-only model', function (): void {
    $posts = PostAttributeOnlyFactory::new()->count(3)->withSlug()->create();

    expect($posts->pluck('slug')->all())->toBe(['hello-world', 'hello-world-2', 'hello-world-3']);
});

// --- slugify:generate command for attribute-only models ---

it('command generates slugs for attribute-only model', function (): void {
    DB::table('posts_with_slug')->insert(['title' => 'Hello World', 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => PostAttributeOnly::class])
        ->assertSuccessful();

    expect(PostAttributeOnly::first()->slug)->toBe('hello-world');
});

it('command skips attribute-only records with empty source attribute', function (): void {
    DB::table('posts_with_slug')->insert(['title' => null, 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => PostAttributeOnly::class])
        ->assertSuccessful();

    expect(PostAttributeOnly::first()->slug)->toBeNull();
});

it('command fails for model without HasSlug trait and without #[Slugify] attribute', function (): void {
    $this->artisan('slugify:generate', ['model' => UserNoSlug::class])
        ->assertFailed();
});

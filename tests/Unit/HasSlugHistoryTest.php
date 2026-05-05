<?php

declare(strict_types=1);

use Tests\Models\PostWithSlugHistory;

it('records old slug in history when slug changes', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'New Title');
    $post->save();

    expect($post->slugHistory)->toHaveCount(1);
    expect($post->slugHistory->first()->slug)->toBe('hello-world');
});

it('does not record history on initial creation', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    expect($post->slugHistory)->toHaveCount(0);
});

it('records multiple slug changes', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'First']);

    $post->setAttribute('title', 'Second');
    $post->save();

    $post->setAttribute('title', 'Third');
    $post->save();

    $post->refresh();

    expect($post->slugHistory)->toHaveCount(2);

    $slugs = $post->slugHistory->pluck('slug')->all();
    expect($slugs)->toContain('first');
    expect($slugs)->toContain('second');
});

it('does not record duplicate slug history entries', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'First']);

    $post->setAttribute('title', 'Second');
    $post->save();

    $post->setAttribute('title', 'First');
    $post->save();

    // "first" was already recorded — should not duplicate.
    $post->setAttribute('title', 'Third');
    $post->save();

    $post->refresh();

    $firstCount = $post->slugHistory->where('slug', 'first')->count();
    expect($firstCount)->toBe(1);
});

it('finds model by current slug via findBySlugWithHistory', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    $found = PostWithSlugHistory::findBySlugWithHistory('hello-world');

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($post->getKey());
});

it('finds model by old slug via findBySlugWithHistory', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Old Title']);

    $post->setAttribute('title', 'New Title');
    $post->save();

    $found = PostWithSlugHistory::findBySlugWithHistory('old-title');

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($post->getKey());
    expect($found->fresh()->getAttribute('slug'))->toBe('new-title');
});

it('returns null when slug is not found in current or history', function (): void {
    expect(PostWithSlugHistory::findBySlugWithHistory('nonexistent'))->toBeNull();
});

it('timestamps history entries', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'New Title');
    $post->save();

    $history = $post->slugHistory->first();
    expect($history->created_at)->not->toBeNull();
});

it('does not record history when slug is not dirty', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    // Only update a non-slug field — slug stays the same.
    $post->setAttribute('title', 'Hello World');
    $post->save();

    expect($post->slugHistory)->toHaveCount(0);
});

it('does not record history when slug resolves to same value', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    // Manually set slug to the same value — dirty but unchanged.
    $post->setAttribute('slug', 'hello-world');
    $post->save();

    expect($post->slugHistory)->toHaveCount(0);
});

it('resolves route binding by primary key when field is null and route key is not the slug column', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    $found = (new PostWithSlugHistory)->resolveRouteBinding($post->getKey());

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($post->getKey());
});

it('slug history entry belongs to the sluggable model', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    $post->setAttribute('title', 'New Title');
    $post->save();

    $history = $post->slugHistory->first();
    $sluggable = $history->sluggable;

    expect($sluggable)->not->toBeNull();
    expect($sluggable->getKey())->toBe($post->getKey());
});

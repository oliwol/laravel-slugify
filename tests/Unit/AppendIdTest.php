<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tests\Models\PostWithAppendId;
use Tests\Models\UserWithAttribute;
use Tests\Models\UserWithRouteBinding;

beforeEach(function (): void {
    Route::get('/posts/{post}', fn (PostWithAppendId $post) => response()->json([
        'id' => $post->id,
        'slug' => $post->slug,
    ]))
        ->middleware([SubstituteBindings::class, 'slug.redirect'])
        ->name('posts.show');
});

it('builds a route key combining slug and primary key', function (): void {
    $post = PostWithAppendId::create(['title' => 'Hello World']);

    expect($post->getRouteKey())->toBe('hello-world-'.$post->id);
});

it('keeps the slug clean for duplicate titles (no numeric increment)', function (): void {
    $first = PostWithAppendId::create(['title' => 'Same Title']);
    $second = PostWithAppendId::create(['title' => 'Same Title']);

    expect($first->fresh()->slug)->toBe('same-title')
        ->and($second->fresh()->slug)->toBe('same-title');
});

it('resolves the model by the ID suffix in the route key', function (): void {
    $post = PostWithAppendId::create(['title' => 'Hello World']);

    $this->getJson('/posts/hello-world-'.$post->id)
        ->assertOk()
        ->assertJson(['id' => $post->id, 'slug' => 'hello-world']);
});

it('passes through without redirect when the slug is current', function (): void {
    $post = PostWithAppendId::create(['title' => 'Hello World']);

    $this->get('/posts/hello-world-'.$post->id)
        ->assertOk();
});

it('redirects 308 to the canonical URL when the slug is stale', function (): void {
    $post = PostWithAppendId::create(['title' => 'Hello World']);
    $post->update(['title' => 'Updated Title']);

    $this->get('/posts/hello-world-'.$post->id)
        ->assertRedirect('/posts/updated-title-'.$post->id)
        ->assertStatus(308);
});

it('preserves the query string on a canonical redirect', function (): void {
    $post = PostWithAppendId::create(['title' => 'Hello World']);
    $post->update(['title' => 'Updated Title']);

    $this->get('/posts/hello-world-'.$post->id.'?ref=newsletter')
        ->assertRedirect('/posts/updated-title-'.$post->id.'?ref=newsletter');
});

it('resolves correctly when the slug itself ends in a number', function (): void {
    $post = PostWithAppendId::create(['title' => 'Hello World 2']);

    expect($post->getRouteKey())->toBe('hello-world-2-'.$post->id);

    $this->getJson('/posts/hello-world-2-'.$post->id)
        ->assertOk()
        ->assertJson(['id' => $post->id, 'slug' => 'hello-world-2']);
});

it('reports isIdAnchored true only when appendId is enabled', function (): void {
    expect((new PostWithAppendId)->isIdAnchored())->toBeTrue()
        ->and((new UserWithAttribute)->isIdAnchored())->toBeFalse();
});

it('returns the plain route key when appendId is disabled', function (): void {
    $user = UserWithRouteBinding::create(['name' => 'John Doe']);

    expect($user->getRouteKey())->toBe('john-doe');
});

it('does not resolve a model when the route key has no ID suffix', function (): void {
    PostWithAppendId::create(['title' => 'Hello World']);

    $this->get('/posts/hello-world')->assertNotFound();
});

it('extracts no ID when the route value is not a string', function (): void {
    $post = new PostWithAppendId;

    $result = $post->resolveRouteBindingQuery(PostWithAppendId::query(), ['not-a-string']);

    expect($result->first())->toBeNull();
});

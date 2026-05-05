<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tests\Models\PostWithSlugHistory;
use Tests\Models\UserWithAttribute;

beforeEach(function (): void {
    Route::get('/posts/{post:slug}', fn (PostWithSlugHistory $post) => response()->json(['slug' => $post->slug]))
        ->middleware([SubstituteBindings::class, 'slug.redirect'])
        ->name('posts.show');

    Route::get('/users/{user}', fn (UserWithAttribute $user) => response()->json(['slug' => $user->slug]))
        ->middleware([SubstituteBindings::class, 'slug.redirect']);
});

it('passes through when the slug is current', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);

    $this->getJson('/posts/hello-world')
        ->assertOk()
        ->assertJson(['slug' => 'hello-world']);
});

it('redirects 301 when accessing a historical slug', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);
    $post->update(['title' => 'Updated Title']);

    $this->get('/posts/hello-world')
        ->assertRedirect('/posts/updated-title')
        ->assertStatus(301);
});

it('redirect target preserves the query string', function (): void {
    $post = PostWithSlugHistory::create(['title' => 'Hello World']);
    $post->update(['title' => 'Updated Title']);

    $this->get('/posts/hello-world?ref=newsletter')
        ->assertRedirect('/posts/updated-title?ref=newsletter');
});

it('passes through for models without HasSlugHistory', function (): void {
    $user = UserWithAttribute::create(['name' => 'John Doe']);

    $this->getJson('/users/'.$user->id)
        ->assertOk()
        ->assertJson(['slug' => 'john-doe']);
});

it('passes through when route parameter is not a model', function (): void {
    Route::get('/items/{id}', fn (string $id) => response()->json(['id' => $id]))
        ->middleware(['slug.redirect']);

    $this->getJson('/items/42')
        ->assertOk()
        ->assertJson(['id' => '42']);
});

it('uses the configured redirect status code', function (): void {
    config(['slugify.redirect_status' => 302]);

    $post = PostWithSlugHistory::create(['title' => 'Hello World']);
    $post->update(['title' => 'Updated Title']);

    $this->get('/posts/hello-world')
        ->assertRedirect('/posts/updated-title')
        ->assertStatus(302);
});

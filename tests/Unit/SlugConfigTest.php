<?php

declare(strict_types=1);

use Oliwol\Slugify\SlugConfig;
use Oliwol\Slugify\Slugify;
use Tests\Models\PostWithSlugConfig;
use Tests\Models\PostWithSlugConfigNoRegenerate;
use Tests\Models\PostWithSlugConfigOverAttribute;
use Tests\Models\UserWithSlugConfigClosure;

it('builds a configuration fluently', function (): void {
    $config = SlugConfig::create()
        ->from('title')
        ->to('slug')
        ->separator('_')
        ->maxLength(30)
        ->regenerateOnUpdate(false)
        ->routeBinding();

    expect($config->getFrom())->toBe('title')
        ->and($config->getTo())->toBe('slug')
        ->and($config->getSeparator())->toBe('_')
        ->and($config->getMaxLength())->toBe(30)
        ->and($config->shouldRegenerateOnUpdate())->toBeFalse()
        ->and($config->usesRouteBinding())->toBeTrue();
});

it('exposes sensible defaults', function (): void {
    $config = SlugConfig::create();

    expect($config->getFrom())->toBe('')
        ->and($config->getTo())->toBeNull()
        ->and($config->getSeparator())->toBeNull()
        ->and($config->getMaxLength())->toBeNull()
        ->and($config->shouldRegenerateOnUpdate())->toBeTrue()
        ->and($config->usesRouteBinding())->toBeFalse();
});

it('creates a configuration from a Slugify attribute', function (): void {
    $config = SlugConfig::fromAttribute(new Slugify(
        from: 'name',
        to: 'slug',
        separator: '+',
        maxLength: 10,
        regenerateOnUpdate: false,
        routeBinding: true,
    ));

    expect($config->getFrom())->toBe('name')
        ->and($config->getTo())->toBe('slug')
        ->and($config->getSeparator())->toBe('+')
        ->and($config->getMaxLength())->toBe(10)
        ->and($config->shouldRegenerateOnUpdate())->toBeFalse()
        ->and($config->usesRouteBinding())->toBeTrue();
});

it('creates a slug via SlugConfig with custom separator', function (): void {
    $post = PostWithSlugConfig::create(['title' => 'Hello World']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello_world');
});

it('truncates a slug according to SlugConfig maxLength', function (): void {
    $post = PostWithSlugConfig::create(['title' => 'Hello Beautiful Wonderful World']);

    expect($post->fresh()->getAttribute('slug'))->toBe('hello_beautiful');
});

it('appends an increment for duplicate SlugConfig slugs', function (): void {
    PostWithSlugConfig::create(['title' => 'Same Title']);
    $second = PostWithSlugConfig::create(['title' => 'Same Title']);

    expect($second->fresh()->getAttribute('slug'))->toBe('same_title_2');
});

it('regenerates a slug on update by default with SlugConfig', function (): void {
    $post = PostWithSlugConfig::create(['title' => 'First']);
    $post->update(['title' => 'Second']);

    expect($post->fresh()->getAttribute('slug'))->toBe('second');
});

it('does not regenerate a slug on update when SlugConfig disables it', function (): void {
    $post = PostWithSlugConfigNoRegenerate::create(['title' => 'Original Title']);

    expect($post->fresh()->getAttribute('slug'))->toBe('original-title');

    $post->update(['title' => 'Changed Title']);

    expect($post->fresh()->getAttribute('slug'))->toBe('original-title');
});

it('creates a slug from a closure source', function (): void {
    $user = UserWithSlugConfigClosure::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe');
});

it('is not sluggable when the closure source resolves to an empty value', function (): void {
    $user = UserWithSlugConfigClosure::create([
        'first_name' => null,
        'last_name' => null,
    ]);

    expect($user->fresh()->getAttribute('slug'))->toBeNull();
});

it('does not regenerate a closure slug when the source is unchanged', function (): void {
    $user = UserWithSlugConfigClosure::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $user->update(['email' => 'john@example.com']);

    expect($user->fresh()->getAttribute('slug'))->toBe('john-doe');
});

it('prefers slugConfig() over the #[Slugify] attribute', function (): void {
    $post = PostWithSlugConfigOverAttribute::create(['title' => 'Hello World']);

    // The attribute defines separator "-", but slugConfig() defines "_" and wins.
    expect($post->fresh()->getAttribute('slug'))->toBe('hello_world');
});

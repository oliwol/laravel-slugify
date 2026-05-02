<?php

declare(strict_types=1);

use Oliwol\Slugify\Rules\SlugRule;
use Tests\Models\UserHasScope;
use Tests\Models\UserWithAttribute;

it('passes when slug does not exist', function (): void {
    $failed = false;

    new SlugRule(UserWithAttribute::class)->validate('slug', 'john-doe', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails when slug already exists', function (): void {
    UserWithAttribute::create(['name' => 'John Doe']);

    $failed = false;

    new SlugRule(UserWithAttribute::class)->validate('slug', 'john-doe', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('passes for update when ignoring the owning model', function (): void {
    $user = UserWithAttribute::create(['name' => 'John Doe']);

    $failed = false;

    SlugRule::for(UserWithAttribute::class)
        ->ignore($user)
        ->validate('slug', 'john-doe', function () use (&$failed): void {
            $failed = true;
        });

    expect($failed)->toBeFalse();
});

it('fails for update when slug belongs to a different model', function (): void {
    UserWithAttribute::create(['name' => 'John Doe']);
    $other = UserWithAttribute::create(['name' => 'Jane Doe']);

    $failed = false;

    SlugRule::for(UserWithAttribute::class)
        ->ignore($other)
        ->validate('slug', 'john-doe', function () use (&$failed): void {
            $failed = true;
        });

    expect($failed)->toBeTrue();
});

it('passes when same slug exists in a different scope', function (): void {
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 1]);

    $failed = false;

    SlugRule::for(UserHasScope::class)
        ->scope('tenant_id', 2)
        ->validate('slug', 'john-doe', function () use (&$failed): void {
            $failed = true;
        });

    expect($failed)->toBeFalse();
});

it('fails when same slug exists within the same scope', function (): void {
    UserHasScope::create(['name' => 'John Doe', 'tenant_id' => 1]);

    $failed = false;

    SlugRule::for(UserHasScope::class)
        ->scope('tenant_id', 1)
        ->validate('slug', 'john-doe', function () use (&$failed): void {
            $failed = true;
        });

    expect($failed)->toBeTrue();
});

it('can be constructed via static for() method', function (): void {
    $failed = false;

    SlugRule::for(UserWithAttribute::class)->validate('slug', 'no-match', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('provides a translatable error message', function (): void {
    UserWithAttribute::create(['name' => 'John Doe']);

    $messages = [];

    new SlugRule(UserWithAttribute::class)->validate('slug', 'john-doe', function (string $msg) use (&$messages): void {
        $messages[] = $msg;
    });

    expect($messages)->toHaveCount(1)
        ->and($messages[0])->toBeString()->not->toBeEmpty();
});

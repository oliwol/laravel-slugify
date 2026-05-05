<?php

declare(strict_types=1);

use Oliwol\Slugify\Rules\SlugFormat;

it('passes for a valid slug', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'hello-world', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('passes for a single word', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'hello', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('passes for a slug with numbers', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'hello-world-123', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails for uppercase letters', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'Hello-World', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('fails for a leading separator', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', '-hello', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('fails for a trailing separator', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'hello-', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('fails for consecutive separators', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'hello--world', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('fails for special characters', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 'hello world', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('fails for an empty string', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', '', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('fails for a non-string value', function (): void {
    $failed = false;

    new SlugFormat()->validate('slug', 123, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('passes with a custom separator', function (): void {
    $failed = false;

    new SlugFormat(separator: '_')->validate('slug', 'hello_world', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('fails when the default separator is used with a custom separator configured', function (): void {
    $failed = false;

    new SlugFormat(separator: '_')->validate('slug', 'hello-world', function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

it('provides a translatable error message', function (): void {
    $messages = [];

    new SlugFormat()->validate('slug', 'INVALID', function (string $msg) use (&$messages): void {
        $messages[] = $msg;
    });

    expect($messages)->toHaveCount(1)
        ->and($messages[0])->toBeString()->not->toBeEmpty();
});

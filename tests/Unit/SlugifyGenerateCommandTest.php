<?php

declare(strict_types=1);

use Tests\Models\UserWithAttribute;

it('generates slugs for records without a slug', function (): void {
    UserWithAttribute::forceCreate(['name' => 'John Doe', 'slug' => null]);
    UserWithAttribute::forceCreate(['name' => 'Jane Doe', 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class])
        ->assertSuccessful();

    expect(UserWithAttribute::first()->slug)->toBe('john-doe');
    expect(UserWithAttribute::orderBy('id', 'desc')->first()->slug)->toBe('jane-doe');
});

it('skips records that already have a slug', function (): void {
    UserWithAttribute::forceCreate(['name' => 'John Doe', 'slug' => 'existing']);
    UserWithAttribute::forceCreate(['name' => 'Jane Doe', 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class])
        ->assertSuccessful();

    expect(UserWithAttribute::first()->slug)->toBe('existing');
    expect(UserWithAttribute::orderBy('id', 'desc')->first()->slug)->toBe('jane-doe');
});

it('overwrites existing slugs with --force', function (): void {
    UserWithAttribute::forceCreate(['name' => 'New Name', 'slug' => 'old-slug']);

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class, '--force' => true])
        ->assertSuccessful();

    expect(UserWithAttribute::first()->slug)->toBe('new-name');
});

it('previews changes with --dry-run without saving', function (): void {
    // Insert directly to bypass the saving event that auto-generates a slug.
    Illuminate\Support\Facades\DB::table('users')->insert(['name' => 'John Doe', 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class, '--dry-run' => true])
        ->assertSuccessful();

    expect(UserWithAttribute::first()->slug)->toBeNull();
});

it('shows info when nothing to process', function (): void {
    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class])
        ->expectsOutputToContain('No records to process')
        ->assertSuccessful();
});

it('fails when class does not exist', function (): void {
    $this->artisan('slugify:generate', ['model' => 'App\\Models\\NonExistent'])
        ->assertFailed();
});

it('fails when class does not use HasSlug', function (): void {
    $this->artisan('slugify:generate', ['model' => Illuminate\Database\Eloquent\Model::class])
        ->assertFailed();
});

it('does not change slug when force regenerates to same value', function (): void {
    UserWithAttribute::forceCreate(['name' => 'John Doe', 'slug' => 'john-doe']);

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class, '--force' => true])
        ->assertSuccessful();

    // Slug should stay the same since it would regenerate to the same value.
    expect(UserWithAttribute::first()->slug)->toBe('john-doe');
});

it('skips records with empty source attributes', function (): void {
    Illuminate\Support\Facades\DB::table('users')->insert(['name' => null, 'slug' => null]);

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class])
        ->assertSuccessful();

    expect(UserWithAttribute::first()->slug)->toBeNull();
});

it('processes large datasets in chunks', function (): void {
    for ($i = 1; $i <= 5; $i++) {
        UserWithAttribute::forceCreate(['name' => "User {$i}", 'slug' => null]);
    }

    $this->artisan('slugify:generate', ['model' => UserWithAttribute::class])
        ->assertSuccessful();

    expect(UserWithAttribute::whereNull('slug')->count())->toBe(0);
});

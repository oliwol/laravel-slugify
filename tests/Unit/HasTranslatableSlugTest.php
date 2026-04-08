<?php

declare(strict_types=1);

use Tests\Models\PostTranslatable;
use Tests\Models\PostTranslatableMethod;
use Tests\Models\PostTranslatableNoRegenerate;

it('generates a slug for each locale', function (): void {
    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    expect($post->getTranslation('slug', 'en'))->toBe('hello-world');
    expect($post->getTranslation('slug', 'de'))->toBe('hallo-welt');
});

it('checks uniqueness per locale', function (): void {
    PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Erster Beitrag'],
    ]);

    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Zweiter Beitrag'],
    ]);

    expect($post->getTranslation('slug', 'en'))->toBe('hello-world-2');
    expect($post->getTranslation('slug', 'de'))->toBe('zweiter-beitrag');
});

it('does not regenerate slug for unchanged locale', function (): void {
    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    $post->setTranslation('title', 'de', 'Neuer Titel');
    $post->save();

    expect($post->getTranslation('slug', 'en'))->toBe('hello-world');
    expect($post->getTranslation('slug', 'de'))->toBe('neuer-titel');
});

it('finds a model by slug for a specific locale', function (): void {
    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    $foundEn = PostTranslatable::findBySlug('hello-world', 'en');
    $foundDe = PostTranslatable::findBySlug('hallo-welt', 'de');

    expect($foundEn)->not->toBeNull();
    expect($foundEn->getKey())->toBe($post->getKey());
    expect($foundDe)->not->toBeNull();
    expect($foundDe->getKey())->toBe($post->getKey());
});

it('does not find a slug across different locales', function (): void {
    PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    expect(PostTranslatable::findBySlug('hello-world', 'de'))->toBeNull();
    expect(PostTranslatable::findBySlug('hallo-welt', 'en'))->toBeNull();
});

it('uses current app locale when no locale is given to findBySlug', function (): void {
    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    app()->setLocale('de');

    $found = PostTranslatable::findBySlug('hallo-welt');

    expect($found)->not->toBeNull();
    expect($found->getKey())->toBe($post->getKey());

    app()->setLocale('en');
});

it('does not override manually set translated slugs', function (): void {
    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
        'slug' => ['en' => 'custom-en'],
    ]);

    expect($post->getTranslation('slug', 'en'))->toBe('custom-en');
    expect($post->getTranslation('slug', 'de'))->toBe('hallo-welt');
});

it('regenerates slug for changed locale on update', function (): void {
    $post = PostTranslatable::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    $post->setTranslation('title', 'en', 'New Title');
    $post->save();

    expect($post->getTranslation('slug', 'en'))->toBe('new-title');
    expect($post->getTranslation('slug', 'de'))->toBe('hallo-welt');
});

// --- Method source ---

it('generates per-locale slugs from a method source', function (): void {
    $post = PostTranslatableMethod::create([
        'title' => ['en' => 'Hello', 'de' => 'Hallo'],
    ]);

    expect($post->getTranslation('slug', 'en'))->toBe('post-hello');
    expect($post->getTranslation('slug', 'de'))->toBe('post-hallo');
});

it('always regenerates slug from method source on update', function (): void {
    $post = PostTranslatableMethod::create([
        'title' => ['en' => 'Hello', 'de' => 'Hallo'],
    ]);

    $post->setTranslation('title', 'en', 'World');
    $post->save();

    expect($post->getTranslation('slug', 'en'))->toBe('post-world');
});

// --- Regenerate on update disabled ---

it('does not regenerate slug per locale on update when disabled', function (): void {
    $post = PostTranslatableNoRegenerate::create([
        'title' => ['en' => 'Hello World', 'de' => 'Hallo Welt'],
    ]);

    $post->setTranslation('title', 'en', 'Changed');
    $post->save();

    expect($post->getTranslation('slug', 'en'))->toBe('hello-world');
});

// --- isSluggable edge cases ---

it('is not sluggable when title has no translations', function (): void {
    $post = new PostTranslatable;

    expect($post->isSluggable())->toBeFalse();
});

it('is not sluggable when target equals primary key', function (): void {
    $post = new class extends Illuminate\Database\Eloquent\Model
    {
        use Oliwol\Slugify\HasTranslatableSlug;
        use Spatie\Translatable\HasTranslations;

        public $timestamps = false;

        public array $translatable = ['id'];

        protected $table = 'posts_translatable';

        protected $guarded = [];

        public function getAttributeToCreateSlugFrom(): string
        {
            return 'id';
        }

        public function getAttributeToSaveSlugTo(): string
        {
            return 'id';
        }
    };

    expect($post->isSluggable())->toBeFalse();
});

it('falls back to single-source isSluggable when source is an array', function (): void {
    $post = new class extends Illuminate\Database\Eloquent\Model
    {
        use Oliwol\Slugify\HasTranslatableSlug;
        use Spatie\Translatable\HasTranslations;

        public $timestamps = false;

        public array $translatable = ['title', 'slug'];

        protected $table = 'posts_translatable';

        protected $guarded = [];

        public function getAttributeToCreateSlugFrom(): array
        {
            return ['title'];
        }

        public function getAttributeToSaveSlugTo(): string
        {
            return 'slug';
        }
    };

    // Array source goes through single-source path; no filled non-translatable attribute → not sluggable.
    expect($post->isSluggable())->toBeFalse();
});

it('skips locale generation when method source returns empty string', function (): void {
    $post = new class extends Illuminate\Database\Eloquent\Model
    {
        use Oliwol\Slugify\HasTranslatableSlug;
        use Spatie\Translatable\HasTranslations;

        public $timestamps = false;

        public array $translatable = ['title', 'slug'];

        protected $table = 'posts_translatable';

        protected $guarded = [];

        public function getAttributeToCreateSlugFrom(): string
        {
            return 'buildSlug';
        }

        public function getAttributeToSaveSlugTo(): string
        {
            return 'slug';
        }

        public function buildSlug(): string
        {
            return ''; // empty → should be skipped
        }
    };

    $post->setTranslations('title', ['en' => 'Hello']);
    $post->save();

    expect($post->getTranslations('slug'))->toBe([]);
});

// --- LogicException for unsupported sources ---

it('throws when source is an array', function (): void {
    $post = new class extends Illuminate\Database\Eloquent\Model
    {
        use Oliwol\Slugify\HasTranslatableSlug;
        use Spatie\Translatable\HasTranslations;

        public $timestamps = false;

        public array $translatable = ['title', 'slug'];

        protected $table = 'posts_translatable';

        protected $guarded = [];

        public function getAttributeToCreateSlugFrom(): array
        {
            return ['title', 'subtitle'];
        }

        public function getAttributeToSaveSlugTo(): string
        {
            return 'slug';
        }
    };

    $post->setTranslations('title', ['en' => 'Hello']);

    expect(fn () => $post->createSlug())->toThrow(LogicException::class);
});

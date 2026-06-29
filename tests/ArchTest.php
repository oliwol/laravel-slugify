<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->strict()
    ->ignoring([
        Oliwol\Slugify\SlugHistory::class,
        Oliwol\Slugify\SlugConfig::class,
    ]);

arch()->preset()->security();

arch('global')
    ->expect(['dd', 'dump', 'ray', 'die', 'var_dump', 'sleep', 'usleep', 'dispatch', 'dispatch_sync'])
    ->not->toBeUsed();

arch('strict types')
    ->expect('Oliwol\Slugify')
    ->toUseStrictTypes();

arch('avoid open for extension')
    ->expect('Oliwol\Slugify')
    ->classes()
    ->toBeFinal();

arch('ensure no extends')
    ->expect('Oliwol\Slugify')
    ->classes()
    ->not->toBeAbstract();

arch('avoid mutation')
    ->expect(Oliwol\Slugify\Slugify::class)
    ->toBeReadonly();

arch('avoid inheritance')
    ->expect(Oliwol\Slugify\Slugify::class)
    ->toExtendNothing();

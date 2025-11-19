<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->strict();

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
    ->expect('Oliwol\Slugify')
    ->classes()
    ->toBeReadonly();

arch('avoid inheritance')
    ->expect('Oliwol\Slugify')
    ->classes()
    ->toExtendNothing();

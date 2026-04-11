<?php

declare(strict_types=1);

namespace Oliwol\Slugify\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Oliwol\Slugify\HasSlug;

final class SlugifyGenerateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'slugify:generate
        {model : The fully qualified model class name}
        {--force : Overwrite existing slugs}
        {--dry-run : Preview changes without saving}';

    /**
     * @var string
     */
    protected $description = 'Generate or regenerate slugs for existing database records';

    public function handle(): int
    {
        $modelClass = $this->argument('model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            $this->error('The provided model class does not exist.');

            return self::FAILURE;
        }

        if (! in_array(HasSlug::class, class_uses_recursive($modelClass), true)) {
            $this->error("Class [$modelClass] does not use the HasSlug trait.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        /** @var Model $instance */
        $instance = new $modelClass;

        /** @var string $target */
        $target = $instance->getAttributeToSaveSlugTo(); // @phpstan-ignore method.notFound

        $total = $this->buildQuery($instance, $target, $force)->count();

        if ($total === 0) {
            $this->info('No records to process.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[Dry Run] ' : '')."Processing $total record(s)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;

        $this->buildQuery($instance, $target, $force)
            ->chunkById(200, function ($models) use ($target, $dryRun, $bar, &$processed): void {
                foreach ($models as $model) {
                    /** @var Model $model */
                    $oldSlug = $model->getAttribute($target);

                    /** @var list<string> $sources */
                    $sources = (array) $model->getAttributeToCreateSlugFrom(); // @phpstan-ignore method.notFound

                    $value = collect($sources)
                        ->map(fn (string $field): mixed => $model->getAttribute($field))
                        ->filter(fn (mixed $field): bool => filled($field) && is_string($field))
                        ->implode(' ');

                    if (! filled($value)) {
                        $bar->advance();

                        continue;
                    }

                    /** @var string $newSlug */
                    $newSlug = $model->incrementSlugIfExists( // @phpstan-ignore method.notFound
                        slug: $model->slugify($value), // @phpstan-ignore method.notFound
                    );

                    if ($oldSlug === $newSlug) {
                        $bar->advance();

                        continue;
                    }

                    $model->setAttribute($target, $newSlug);

                    if (! $dryRun) {
                        $model->saveQuietly();
                    }

                    $processed++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $action = $dryRun ? 'Would update' : 'Updated';
        $this->info("$action $processed slug(s).");

        return self::SUCCESS;
    }

    /**
     * @return Builder<Model>
     */
    private function buildQuery(Model $instance, string $target, bool $force): Builder
    {
        $query = $instance->newQuery();

        if (! $force) {
            $query->where(function (Builder $q) use ($target): void {
                $q->whereNull($target)->orWhere($target, '');
            });
        }

        return $query;
    }
}

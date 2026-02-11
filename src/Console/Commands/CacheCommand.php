<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Cache\TranslatableCache;
use Marshmallow\Translatable\Cache\TranslationCache;
use Marshmallow\Translatable\Models\Translatable;

class CacheCommand extends Command
{
    protected $signature = 'translatable:cache
                            {--clear : Clear the cache before rebuilding}
                            {--model= : Only cache translations for a specific model class}';

    protected $description = 'Build translation caches for improved performance';

    public function handle(): int
    {
        if ($this->option('clear')) {
            $this->call('translatable:clear');
        }

        $this->cacheTranslations();

        $this->cacheTranslatables();

        $this->info('Translation caches built successfully.');

        return self::SUCCESS;
    }

    protected function cacheTranslations(): void
    {
        $this->info('Building translation cache...');

        TranslationCache::build();

        $this->info('Translation cache built: ' . TranslationCache::getCachePath());
    }

    protected function cacheTranslatables(): void
    {
        $modelClass = $this->option('model');

        if ($modelClass) {
            $this->info("Building cache for model: {$modelClass}");

            TranslatableCache::buildForModel($modelClass);

            $this->info('Model cache built: ' . TranslatableCache::getModelCachePath($modelClass));

            return;
        }

        $this->info('Building translatable caches...');

        $modelClasses = Translatable::query()
            ->select('translatable_type')
            ->distinct()
            ->pluck('translatable_type');

        foreach ($modelClasses as $modelClass) {
            $this->line("  - {$modelClass}");

            TranslatableCache::buildForModel($modelClass);
        }

        $this->info('Translatable caches built: ' . TranslatableCache::getCachePath());
    }
}

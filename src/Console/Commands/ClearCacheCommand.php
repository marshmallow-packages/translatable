<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Cache\TranslatableCache;
use Marshmallow\Translatable\Cache\TranslationCache;

class ClearCacheCommand extends Command
{
    protected $signature = 'translatable:clear
                            {--model= : Only clear cache for a specific model class}';

    protected $description = 'Clear translation caches';

    public function handle(): int
    {
        $modelClass = $this->option('model');

        if ($modelClass) {
            TranslatableCache::clearModel($modelClass);

            $this->info("Cache cleared for model: {$modelClass}");

            return self::SUCCESS;
        }

        TranslationCache::clear();
        TranslatableCache::clear();

        $this->info('All translation caches cleared.');

        return self::SUCCESS;
    }
}

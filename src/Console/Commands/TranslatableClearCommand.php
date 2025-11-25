<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Cache\TranslatableCache;

class TranslatableClearCommand extends Command
{
    protected $signature = 'translatable:clear
                            {--model= : Clear cache for a specific model class only}';

    protected $description = 'Clear the cached model translations';

    public function handle(): int
    {
        $specificModel = $this->option('model');

        if ($specificModel) {
            if (TranslatableCache::modelCacheExists($specificModel)) {
                TranslatableCache::clearModel($specificModel);
                $this->components->info("Cache cleared for model: {$specificModel}");
            } else {
                $this->components->warn("No cache found for model: {$specificModel}");
            }

            return self::SUCCESS;
        }

        if (! TranslatableCache::cacheExists()) {
            $this->components->warn('No translation cache exists.');

            return self::SUCCESS;
        }

        TranslatableCache::clear();

        $this->components->info('Translation cache cleared successfully.');

        return self::SUCCESS;
    }
}

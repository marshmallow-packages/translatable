<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Cache\TranslationCache;

class TranslationClearCommand extends Command
{
    protected $signature = 'translation:clear';

    protected $description = 'Clear the cached code string translations';

    public function handle(): int
    {
        if (! TranslationCache::cacheExists()) {
            $this->components->warn('No translation cache exists.');

            return self::SUCCESS;
        }

        TranslationCache::clear();

        $this->components->info('Translation cache cleared successfully.');

        return self::SUCCESS;
    }
}

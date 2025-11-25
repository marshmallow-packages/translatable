<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Cache\TranslationCache;

class TranslationCacheCommand extends Command
{
    protected $signature = 'translation:cache
                            {--clear : Clear cache before regenerating}';

    protected $description = 'Cache all code string translations for faster retrieval';

    public function handle(): int
    {
        if ($this->option('clear')) {
            TranslationCache::clear();
            $this->components->info('Existing cache cleared.');
        }

        $this->components->info('Caching code string translations...');

        $languageModel = config('translatable.models.language');
        $translationModel = config('translatable.models.translation');

        $languages = $languageModel::all();

        if ($languages->isEmpty()) {
            $this->components->warn('No languages found.');

            return self::SUCCESS;
        }

        $cache = [];
        $totalCount = 0;

        $progressBar = $this->output->createProgressBar($languages->count());
        $progressBar->start();

        foreach ($languages as $language) {
            $languageCode = $language->language;
            $cache[$languageCode] = [
                'group' => [],
                'single' => [],
            ];

            $translations = $translationModel::where('language_id', $language->id)->get();

            foreach ($translations as $translation) {
                $group = $translation->group;
                $key = $translation->key;
                $value = $translation->value;

                $isSingle = str_ends_with($group, 'single') || $group === null;

                if ($isSingle) {
                    $groupKey = $group ?: 'single';
                    if (! isset($cache[$languageCode]['single'][$groupKey])) {
                        $cache[$languageCode]['single'][$groupKey] = [];
                    }
                    $cache[$languageCode]['single'][$groupKey][$key] = $value;
                } else {
                    if (! isset($cache[$languageCode]['group'][$group])) {
                        $cache[$languageCode]['group'][$group] = [];
                    }
                    $cache[$languageCode]['group'][$group][$key] = $value;
                }

                $totalCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        TranslationCache::writeCache($cache);

        $this->components->info("Cached {$totalCount} translations for {$languages->count()} languages.");
        $this->components->info('Cache path: ' . TranslationCache::getCachePath());

        return self::SUCCESS;
    }
}

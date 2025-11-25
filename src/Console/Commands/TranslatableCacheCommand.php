<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Cache\TranslatableCache;

class TranslatableCacheCommand extends Command
{
    protected $signature = 'translatable:cache
                            {--model= : Cache only a specific model class}
                            {--clear : Clear cache before regenerating}';

    protected $description = 'Cache all model translations for faster retrieval';

    public function handle(): int
    {
        if ($this->option('clear')) {
            TranslatableCache::clear();
            $this->components->info('Existing cache cleared.');
        }

        $this->components->info('Caching model translations...');

        $translatableModel = config('translatable.models.translatable');
        $specificModel = $this->option('model');

        $query = $translatableModel::query()
            ->select(['translatable_type', 'translatable_id', 'source_field', 'translated_value', 'language_id'])
            ->orderBy('translatable_type')
            ->orderBy('translatable_id');

        if ($specificModel) {
            $query->where('translatable_type', $specificModel);
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->components->warn('No translations found to cache.');

            return self::SUCCESS;
        }

        $this->components->info("Found {$totalCount} translations to cache.");

        $modelCaches = [];
        $currentModel = null;
        $processedCount = 0;

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $query->chunk(1000, function ($translations) use (&$modelCaches, &$currentModel, &$processedCount, $progressBar) {
            foreach ($translations as $translation) {
                $modelClass = $translation->translatable_type;
                $modelId = $translation->translatable_id;
                $field = $translation->source_field;
                $languageId = $translation->language_id;
                $value = $translation->translated_value;

                if (! isset($modelCaches[$modelClass])) {
                    $modelCaches[$modelClass] = [];
                }

                if (! isset($modelCaches[$modelClass][$modelId])) {
                    $modelCaches[$modelClass][$modelId] = [];
                }

                if (! isset($modelCaches[$modelClass][$modelId][$field])) {
                    $modelCaches[$modelClass][$modelId][$field] = [];
                }

                $modelCaches[$modelClass][$modelId][$field][$languageId] = $value;

                $processedCount++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        $this->components->info('Writing cache files...');

        $modelCount = count($modelCaches);
        $modelProgress = $this->output->createProgressBar($modelCount);
        $modelProgress->start();

        foreach ($modelCaches as $modelClass => $data) {
            TranslatableCache::writeModelCache($modelClass, $data);
            $modelProgress->advance();
        }

        $modelProgress->finish();
        $this->newLine();

        $this->components->info("Cached {$processedCount} translations across {$modelCount} models.");
        $this->components->info('Cache path: ' . TranslatableCache::getCachePath());

        return self::SUCCESS;
    }
}

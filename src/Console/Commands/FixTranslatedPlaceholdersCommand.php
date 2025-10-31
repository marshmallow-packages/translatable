<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Support\Facades\DB;
use Marshmallow\Translatable\Console\Commands\BaseCommand;

class FixTranslatedPlaceholdersCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:fix-placeholders {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix translated placeholders in translations by reverting them to their original form';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $this->line('Scanning translations for mistranslated placeholders...');

        $fixed = 0;
        $sourceLanguage = config('translatable.source_language');

        // Get all languages except the source language
        $languages = config('translatable.models.language')::where('language', '!=', $sourceLanguage)->get();

        foreach ($languages as $language) {
            $this->line("Checking {$language->name} ({$language->language})...");

            // Get all translations for this language
            $translations = $language->translations()->get();

            foreach ($translations as $translation) {
                // Skip if value is null or empty
                if (empty($translation->value)) {
                    continue;
                }

                // Get the source translation to compare placeholders
                $sourceTranslation = config('translatable.models.translation')
                    ::join('languages', 'languages.id', '=', 'translations.language_id')
                    ->where('languages.language', $sourceLanguage)
                    ->where('translations.group', $translation->group)
                    ->where('translations.key', $translation->key)
                    ->select('translations.*')
                    ->first();

                if (!$sourceTranslation || empty($sourceTranslation->value)) {
                    continue;
                }

                // Extract placeholders from source and target
                $sourcePlaceholders = $this->extractPlaceholders($sourceTranslation->value);
                $targetPlaceholders = $this->extractPlaceholders($translation->value);

                // Check if placeholders were translated
                $incorrectPlaceholders = $this->findIncorrectPlaceholders($sourcePlaceholders, $targetPlaceholders);

                if (!empty($incorrectPlaceholders)) {
                    $fixedValue = $this->fixPlaceholders($translation->value, $incorrectPlaceholders, $sourcePlaceholders);

                    $this->warn("  Found issue in: {$translation->group}.{$translation->key}");
                    $this->line("    Original: {$translation->value}");
                    $this->line("    Fixed:    {$fixedValue}");

                    if (!$isDryRun) {
                        $translation->update(['value' => $fixedValue]);
                    }

                    $fixed++;
                }
            }
        }

        if ($fixed === 0) {
            return $this->info('No translated placeholders found. All translations look good!');
        }

        if ($isDryRun) {
            return $this->info("Found {$fixed} translation(s) with translated placeholders. Run without --dry-run to fix them.");
        }

        return $this->info("Successfully fixed {$fixed} translation(s)!");
    }

    /**
     * Extract all placeholders from a text
     *
     * @param string $text
     * @return array
     */
    protected function extractPlaceholders($text)
    {
        $placeholders = [];

        // Match {variable}
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $text, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $placeholders[] = [
                    'type' => 'curly',
                    'name' => $match,
                    'full' => '{' . $match . '}',
                ];
            }
        }

        // Match :variable
        preg_match_all('/\b:([a-zA-Z_][a-zA-Z0-9_]*)/', $text, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                $placeholders[] = [
                    'type' => 'colon',
                    'name' => $match,
                    'full' => ':' . $match,
                ];
            }
        }

        // Match {{variable}} or {!! variable !!}
        preg_match_all('/\{\{!?\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*!?\}\}/', $text, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $placeholders[] = [
                    'type' => 'blade',
                    'name' => trim(preg_replace('/[\{\}!]/', '', $match)),
                    'full' => $match,
                ];
            }
        }

        return $placeholders;
    }

    /**
     * Find placeholders that were incorrectly translated
     *
     * @param array $sourcePlaceholders
     * @param array $targetPlaceholders
     * @return array
     */
    protected function findIncorrectPlaceholders($sourcePlaceholders, $targetPlaceholders)
    {
        $incorrect = [];

        // Create a map of source placeholder names
        $sourceNames = array_column($sourcePlaceholders, 'name');

        foreach ($targetPlaceholders as $targetPlaceholder) {
            // Check if this placeholder name exists in source
            if (!in_array($targetPlaceholder['name'], $sourceNames)) {
                // This placeholder was likely translated
                $incorrect[] = $targetPlaceholder;
            }
        }

        return $incorrect;
    }

    /**
     * Fix incorrectly translated placeholders
     *
     * @param string $text
     * @param array $incorrectPlaceholders
     * @param array $sourcePlaceholders
     * @return string
     */
    protected function fixPlaceholders($text, $incorrectPlaceholders, $sourcePlaceholders)
    {
        // Group source placeholders by type
        $sourceByType = [];
        foreach ($sourcePlaceholders as $placeholder) {
            $sourceByType[$placeholder['type']][] = $placeholder;
        }

        // Replace each incorrect placeholder with the corresponding source placeholder
        foreach ($incorrectPlaceholders as $incorrect) {
            $type = $incorrect['type'];

            // Find the corresponding source placeholder of the same type
            if (isset($sourceByType[$type]) && !empty($sourceByType[$type])) {
                // Get the first available source placeholder of this type
                $source = array_shift($sourceByType[$type]);

                // Replace the translated placeholder with the original
                $text = str_replace($incorrect['full'], $source['full'], $text);
            }
        }

        return $text;
    }
}

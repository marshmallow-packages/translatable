<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Actions\ImportLaravelLangAction;
use Marshmallow\Translatable\Models\Language;

class ImportLaravelLangCommand extends Command
{
    protected $signature = 'translatable:import
                            {preset : The laravel-lang preset to import (e.g., laravel, nova, filament, validation)}
                            {--language= : Only import for a specific language code}';

    protected $description = 'Import translations from laravel-lang presets';

    public function handle(ImportLaravelLangAction $action): int
    {
        $preset = $this->argument('preset');
        $languageCode = $this->option('language');

        $this->info("Importing translations from laravel-lang/{$preset}...");

        $language = null;

        if ($languageCode) {
            $language = Language::where('code', $languageCode)->first();

            if (! $language) {
                $this->error("Language not found: {$languageCode}");

                return self::FAILURE;
            }
        }

        $result = $action->handle($preset, $language);

        $this->info("Imported: {$result['imported']}");
        $this->info("Skipped (manual): {$result['skipped']}");
        $this->info("Skipped (locked): {$result['locked']}");

        return self::SUCCESS;
    }
}

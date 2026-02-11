<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Actions\ImportVendorTranslationsAction;
use Marshmallow\Translatable\Models\Language;

class ImportVendorCommand extends Command
{
    protected $signature = 'translatable:import-vendor
                            {namespace : The vendor namespace (e.g., nova, spatie)}
                            {path : Path to the vendor translations directory}
                            {--language= : Only import for a specific language code}';

    protected $description = 'Import translations from a vendor package';

    public function handle(ImportVendorTranslationsAction $action): int
    {
        $namespace = $this->argument('namespace');
        $path = $this->argument('path');
        $languageCode = $this->option('language');

        $this->info("Importing translations from {$namespace}...");

        $language = null;

        if ($languageCode) {
            $language = Language::where('code', $languageCode)->first();

            if (! $language) {
                $this->error("Language not found: {$languageCode}");

                return self::FAILURE;
            }
        }

        $result = $action->handle($namespace, $path, $language);

        if (isset($result['error'])) {
            $this->error($result['error']);

            return self::FAILURE;
        }

        $this->info("Imported: {$result['imported']}");
        $this->info("Skipped (manual): {$result['skipped']}");
        $this->info("Skipped (locked): {$result['locked']}");

        return self::SUCCESS;
    }
}

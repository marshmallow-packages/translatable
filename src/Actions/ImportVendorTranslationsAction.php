<?php

namespace Marshmallow\Translatable\Actions;

use Illuminate\Support\Facades\File;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;

class ImportVendorTranslationsAction
{
    protected int $imported = 0;

    protected int $skipped = 0;

    protected int $locked = 0;

    public function handle(string $namespace, string $path, ?Language $language = null): array
    {
        $this->imported = 0;
        $this->skipped = 0;
        $this->locked = 0;

        if (! File::isDirectory($path)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'locked' => 0,
                'error' => "Path does not exist: {$path}",
            ];
        }

        $languages = $language ? collect([$language]) : Language::activeForTranslations()->get();

        foreach ($languages as $lang) {
            $this->importForLanguage($namespace, $path, $lang);
        }

        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'locked' => $this->locked,
        ];
    }

    protected function importForLanguage(string $namespace, string $basePath, Language $language): void
    {
        $localePath = "{$basePath}/{$language->code}";

        if (! File::isDirectory($localePath)) {
            return;
        }

        $files = File::allFiles($localePath);

        foreach ($files as $file) {
            $this->importFile($namespace, $file->getPathname(), $language);
        }
    }

    protected function importFile(string $namespace, string $path, Language $language): void
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = pathinfo($path, PATHINFO_FILENAME);

        if ($extension === 'php') {
            $translations = require $path;
            $group = "{$namespace}::{$filename}";
        } elseif ($extension === 'json') {
            $translations = json_decode(File::get($path), true) ?? [];
            $group = 'single';
        } else {
            return;
        }

        $this->importTranslations($translations, $group, $language);
    }

    protected function importTranslations(array $translations, string $group, Language $language, string $prefix = ''): void
    {
        foreach ($translations as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->importTranslations($value, $group, $language, $fullKey);

                continue;
            }

            $this->importSingleTranslation($group, $fullKey, $value, $language);
        }
    }

    protected function importSingleTranslation(string $group, string $key, string $value, Language $language): void
    {
        $existing = Translation::query()
            ->where('language_id', $language->id)
            ->where('group', $group)
            ->where('key', $key)
            ->whereNull('context')
            ->first();

        if ($existing) {
            if ($existing->is_locked) {
                $this->locked++;

                return;
            }

            if (config('translatable.import.respect_locked', true) && $existing->source === 'manual') {
                $this->skipped++;

                return;
            }

            $existing->update([
                'value' => $value,
                'source' => 'vendor',
                'imported_at' => now(),
            ]);

            $this->imported++;

            return;
        }

        Translation::create([
            'language_id' => $language->id,
            'group' => $group,
            'key' => $key,
            'context' => null,
            'value' => $value,
            'source' => 'vendor',
            'imported_at' => now(),
        ]);

        $this->imported++;
    }
}

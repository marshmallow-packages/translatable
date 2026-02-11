<?php

namespace Marshmallow\Translatable\Actions;

use Illuminate\Support\Facades\File;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;

class ImportLaravelLangAction
{
    protected int $imported = 0;

    protected int $skipped = 0;

    protected int $locked = 0;

    public function handle(string $preset, ?Language $language = null): array
    {
        $this->imported = 0;
        $this->skipped = 0;
        $this->locked = 0;

        $languages = $language ? collect([$language]) : Language::activeForTranslations()->get();

        foreach ($languages as $lang) {
            $this->importForLanguage($preset, $lang);
        }

        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'locked' => $this->locked,
        ];
    }

    protected function importForLanguage(string $preset, Language $language): void
    {
        $paths = $this->getPresetPaths($preset, $language->code);

        foreach ($paths as $path) {
            if (! File::exists($path)) {
                continue;
            }

            if (File::isDirectory($path)) {
                $this->importDirectory($path, $language);
            } else {
                $this->importFile($path, $language);
            }
        }
    }

    protected function getPresetPaths(string $preset, string $locale): array
    {
        $basePath = base_path("vendor/laravel-lang/{$preset}/locales/{$locale}");

        return [
            $basePath,
            "{$basePath}.json",
        ];
    }

    protected function importDirectory(string $path, Language $language): void
    {
        $files = File::files($path);

        foreach ($files as $file) {
            $this->importFile($file->getPathname(), $language);
        }
    }

    protected function importFile(string $path, Language $language): void
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = pathinfo($path, PATHINFO_FILENAME);

        if ($extension === 'php') {
            $translations = require $path;
            $group = $filename;
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
                'source' => 'laravel-lang',
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
            'source' => 'laravel-lang',
            'imported_at' => now(),
        ]);

        $this->imported++;
    }
}

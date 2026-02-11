<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;

class ScanCommand extends Command
{
    protected $signature = 'translatable:scan
                            {--dry-run : Show what would be added without actually adding}';

    protected $description = 'Scan project files for translation keys and add them to the database';

    protected array $foundKeys = [];

    public function handle(): int
    {
        $this->info('Scanning for translation keys...');

        $paths = config('translatable.scan.paths', [app_path(), resource_path('views')]);
        $patterns = config('translatable.scan.patterns', ['*.php', '*.blade.php', '*.vue']);
        $methods = config('translatable.scan.methods', ['trans', '__', 'trans_choice', '@lang']);

        foreach ($paths as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            $this->scanDirectory($path, $patterns, $methods);
        }

        $this->info("Found " . count($this->foundKeys) . " translation keys.");

        if ($this->option('dry-run')) {
            $this->displayFoundKeys();

            return self::SUCCESS;
        }

        $this->addKeysToDatabase();

        return self::SUCCESS;
    }

    protected function scanDirectory(string $path, array $patterns, array $methods): void
    {
        foreach ($patterns as $pattern) {
            $files = File::glob("{$path}/{$pattern}");

            foreach ($files as $file) {
                $this->scanFile($file, $methods);
            }

            $directories = File::directories($path);

            foreach ($directories as $directory) {
                $this->scanDirectory($directory, $patterns, $methods);
            }
        }
    }

    protected function scanFile(string $file, array $methods): void
    {
        $content = File::get($file);

        foreach ($methods as $method) {
            $this->extractKeys($content, $method);
        }
    }

    protected function extractKeys(string $content, string $method): void
    {
        if ($method === '@lang') {
            preg_match_all("/@lang\(['\"]([^'\"]+)['\"]/", $content, $matches);
        } else {
            preg_match_all("/{$method}\(['\"]([^'\"]+)['\"]/", $content, $matches);
        }

        foreach ($matches[1] ?? [] as $key) {
            if ($this->isValidKey($key)) {
                $this->foundKeys[$key] = $this->parseKey($key);
            }
        }
    }

    protected function isValidKey(string $key): bool
    {
        if (strlen($key) > 255) {
            return false;
        }

        if (str_contains($key, '$')) {
            return false;
        }

        return true;
    }

    protected function parseKey(string $key): array
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key, 2);

            return [
                'group' => $parts[0],
                'key' => $parts[1],
            ];
        }

        return [
            'group' => 'single',
            'key' => $key,
        ];
    }

    protected function displayFoundKeys(): void
    {
        $this->table(
            ['Group', 'Key'],
            collect($this->foundKeys)->map(fn ($data) => [$data['group'], $data['key']])->toArray()
        );
    }

    protected function addKeysToDatabase(): void
    {
        $languages = Language::active()->get();

        if ($languages->isEmpty()) {
            $this->error('No active languages found. Please create at least one language first.');

            return;
        }

        $added = 0;
        $skipped = 0;

        foreach ($this->foundKeys as $fullKey => $data) {
            foreach ($languages as $language) {
                $exists = Translation::query()
                    ->where('language_id', $language->id)
                    ->where('group', $data['group'])
                    ->where('key', $data['key'])
                    ->whereNull('context')
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Translation::create([
                    'language_id' => $language->id,
                    'group' => $data['group'],
                    'key' => $data['key'],
                    'context' => null,
                    'value' => null,
                    'source' => 'scan',
                ]);

                $added++;
            }
        }

        $this->info("Added {$added} translation keys, skipped {$skipped} existing keys.");
    }
}

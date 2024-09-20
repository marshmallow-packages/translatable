<?php

namespace Marshmallow\Translatable\Scanner;

use Illuminate\Filesystem\Filesystem;

class Scanner
{
    private $disk;

    private $scanPaths;

    private $translationMethods;

    public function __construct(Filesystem $disk, $scanPaths, $translationMethods)
    {
        $this->disk = $disk;
        $this->scanPaths = $scanPaths;
        $this->translationMethods = $translationMethods;
    }

    public function findTranslations()
    {
        $results = $this->findDefaultTranslations();
        $results = $this->findValidationAttributes($results);

        return $results;
    }

    /**
     * Scan all the files in the provided $scanPath for translations.
     *
     * @return array
     */
    protected function findDefaultTranslations()
    {
        $results = ['single' => [], 'group' => []];

        // This has been derived from a combination of the following:
        // * Laravel Language Manager GUI from Mohamed Said (https://github.com/themsaid/laravel-langman-gui)
        // * Laravel 5 Translation Manager from Barry vd. Heuvel (https://github.com/barryvdh/laravel-translation-manager)
        $matchingPattern =
            '[^\w]' . // Must not start with any alphanum or _
            '(?<!->)' . // Must not start with ->
            '(' . implode('|', $this->translationMethods) . ')' . // Must start with one of the functions
            "\(" . // Match opening parentheses
            "\s*" . // Match any whitespace
            "[\'\"]" . // Match " or '
            // "\s*" . // Match any whitespace
            '(' . // Start a new group to match:
            '[^\'"\)]+' . // Must start with group
            ')' . // Close group
            "[\'\"]" . // Closing quote
            "\s*" . // Match any whitespace
            "[\),]";  // Close parentheses or new parameter

        foreach ($this->disk->allFiles($this->scanPaths) as $file) {
            if (preg_match_all("/$matchingPattern/siU", $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    if (preg_match("/(^[a-zA-Z0-9:_-]+([.][^\1)\ ]+)+$)/siU", $key, $arrayMatches)) {
                        [$file, $k] = explode('.', $arrayMatches[0], 2);
                        $results['group'][$file][$k] = '';

                        continue;
                    } else {
                        $results['single']['single'][$key] = '';
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Scan all the files in the provided $scanPath for fields names for validation attribute translations.
     *
     * @return array
     */
    protected function findValidationAttributes(array $results)
    {
        if (!array_key_exists('validation', $results['single'])) {
            $results['single']['validation'] = [];
        }

        $scan_paths = [
            base_path('resources/views')
        ];

        foreach ($this->disk->allFiles($scan_paths) as $file) {
            if (preg_match_all("/(name|wire:model)=\"(.+?)\"/", $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    if (preg_match("/(^[a-zA-Z0-9:_-]+([.][^\1)\ ]+)+$)/siU", $key, $arrayMatches)) {
                        continue;
                    } else {
                        $results['single']['validation']["attributes.{$key}"] = '';
                    }
                }
            }
        }

        return $results;
    }
}

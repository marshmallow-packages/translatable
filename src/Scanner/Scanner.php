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
                    // Check if this is a valid group.key pattern
                    // Valid: 'validation.required', 'messages.welcome', 'auth.failed'
                    // Invalid: 'Uploading...', 'file.txt', 'Wait..', 'Hello. World'
                    if ($this->isValidGroupTranslation($key)) {
                        [$file, $k] = explode('.', $key, 2);
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
                    // Skip if this looks like a valid group translation (nested attributes)
                    if ($this->isValidGroupTranslation($key)) {
                        continue;
                    } else {
                        $results['single']['validation']["attributes.{$key}"] = '';
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Determine if a translation key is a valid group.key pattern
     *
     * Valid patterns:
     * - validation.required (alphanumeric group, alphanumeric key with underscores/dashes)
     * - messages.welcome_user (underscores allowed)
     * - auth.failed (simple group.key)
     * - vendor::package.group.key (vendor namespaced)
     *
     * Invalid patterns:
     * - Uploading... (dots at the end)
     * - file.txt (file extension)
     * - Wait.. (double dots)
     * - Hello. World (space after dot)
     * - .hidden (starts with dot)
     * - trailing. (ends with dot)
     *
     * @param string $key
     * @return bool
     */
    protected function isValidGroupTranslation($key)
    {
        // Must contain at least one dot
        if (strpos($key, '.') === false) {
            return false;
        }

        // Cannot start or end with a dot
        if (substr($key, 0, 1) === '.' || substr($key, -1) === '.') {
            return false;
        }

        // Cannot have consecutive dots (like .. or ...)
        if (strpos($key, '..') !== false) {
            return false;
        }

        // Cannot have spaces around dots (like "Hello. World" or "test .key")
        if (preg_match('/\s*\.\s+|\s+\.\s*/', $key)) {
            return false;
        }

        // Split by dot and validate each part
        $parts = explode('.', $key);

        // Need at least 2 parts (group.key)
        if (count($parts) < 2) {
            return false;
        }

        foreach ($parts as $part) {
            // Each part must be non-empty
            if (empty($part)) {
                return false;
            }

            // Handle vendor namespace (package::group.key)
            if (strpos($part, '::') !== false) {
                $namespaceParts = explode('::', $part);
                foreach ($namespaceParts as $nsPart) {
                    if (!$this->isValidTranslationPart($nsPart)) {
                        return false;
                    }
                }
            } else {
                // Regular validation: alphanumeric, underscores, hyphens, asterisks (for wildcards)
                if (!$this->isValidTranslationPart($part)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if a translation part (group or key segment) is valid
     *
     * @param string $part
     * @return bool
     */
    protected function isValidTranslationPart($part)
    {
        // Allow alphanumeric characters, underscores, hyphens, and asterisks
        // Must not be only special characters or numbers
        return preg_match('/^[a-zA-Z0-9_\-\*]+$/', $part) && preg_match('/[a-zA-Z]/', $part);
    }
}

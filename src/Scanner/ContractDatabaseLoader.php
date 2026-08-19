<?php

namespace Marshmallow\Translatable\Scanner;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\FileLoader;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

class ContractDatabaseLoader implements Loader
{
    private $translation;
    private $fileLoader;
    private $hints = [];

    /**
     * Filesystem paths this loader consults for the default namespace.
     *
     * Always empty: translations for the default namespace come from the
     * database, never from disk. It exists because Laravel's own FileLoader
     * declares `$paths` and tooling reflects on it - barryvdh/laravel-ide-helper
     * reads `paths` (falling back to `path`) off the bound loader when it
     * builds .phpstorm.meta.php, and threw
     * "Property ...ContractDatabaseLoader::$path does not exist" when neither
     * was present.
     *
     * @var array
     */
    protected $paths = [];

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
        $this->fileLoader = new FileLoader(app('files'), []);
    }

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if ($group == '*' && $namespace == '*') {
            return $this->translation->getSingleTranslationsFor($locale)->get('single', collect())->toArray();
        }

        if (is_null($namespace) || $namespace == '*') {
            return $this->translation->getGroupTranslationsFor($locale)->filter(function ($value, $key) use ($group) {
                return $key === $group;
            })->first() ?? [];
        }

        // Try database first
        $result = $this->translation->getGroupTranslationsFor($locale)->filter(function ($value, $key) use ($group, $namespace) {
            return $key === "{$namespace}::{$group}";
        })->first();

        // If found in database, return it
        if ($result !== null) {
            return $result;
        }

        // Fall back to file-based loading for registered namespaces
        if (isset($this->hints[$namespace])) {
            return $this->fileLoader->load($locale, $group, $namespace) ?? [];
        }

        return [];
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        // Normalise before storing. Packages almost always register their hint
        // as __DIR__ . '/../resources/lang', which keeps the unresolved
        // `src/../` segment in the string. Consumers that pair the hint with a
        // directory listing then compare an unnormalised prefix against
        // normalised file paths, the prefix never matches, and they fall back
        // to using the whole absolute path - ide-helper emitted translation
        // keys like `nova::.Users.you.project.vendor...validation.attached`
        // instead of `nova::validation.attached`. realpath() returns false for
        // a path that does not exist yet, so keep the raw hint in that case.
        $hint = realpath($hint) ?: $hint;

        $this->hints[$namespace] = $hint;
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path)
    {
        $this->fileLoader->addJsonPath($path);
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * Returns the namespace => hint path map, matching
     * Illuminate\Translation\FileLoader::namespaces(). This previously
     * returned only the namespace names, which silently broke every consumer
     * that expects the map - ide-helper resolved each namespace name as though
     * it were a directory path, found nothing, and emitted no namespaced
     * translations at all.
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }
}

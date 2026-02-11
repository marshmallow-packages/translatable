<?php

namespace Marshmallow\Translatable;

use Illuminate\Contracts\Translation\Loader;
use Marshmallow\Translatable\Cache\TranslationCache;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;

class TranslationLoader implements Loader
{
    protected array $hints = [];

    protected array $loaded = [];

    public function load($locale, $group, $namespace = null): array
    {
        if ($namespace !== null && $namespace !== '*') {
            return $this->loadNamespaced($locale, $group, $namespace);
        }

        return $this->loadFromDatabase($locale, $group);
    }

    protected function loadFromDatabase(string $locale, string $group): array
    {
        $cacheKey = "{$locale}.{$group}";

        if (isset($this->loaded[$cacheKey])) {
            return $this->loaded[$cacheKey];
        }

        if (TranslatableConfig::isCacheEnabled()) {
            $cached = TranslationCache::get($locale, $group);

            if ($cached !== null) {
                $this->loaded[$cacheKey] = $cached;

                return $cached;
            }
        }

        $translations = $this->queryTranslations($locale, $group);

        $this->loaded[$cacheKey] = $translations;

        return $translations;
    }

    protected function queryTranslations(string $locale, string $group): array
    {
        $language = Language::where('code', $locale)->first();

        if (! $language) {
            return [];
        }

        $query = Translation::query()
            ->where('language_id', $language->id)
            ->where('group', $group)
            ->whereNotNull('value')
            ->where('value', '!=', '');

        $translations = [];

        foreach ($query->get() as $translation) {
            $key = $translation->context
                ? "{$translation->key}.{$translation->context}"
                : $translation->key;

            $translations[$key] = $translation->value;
        }

        return $translations;
    }

    protected function loadNamespaced(string $locale, string $group, string $namespace): array
    {
        if (isset($this->hints[$namespace])) {
            return $this->loadFromDatabase($locale, "{$namespace}::{$group}");
        }

        return [];
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->hints[$namespace] = $hint;
    }

    public function addJsonPath($path): void
    {
    }

    public function namespaces(): array
    {
        return $this->hints;
    }
}

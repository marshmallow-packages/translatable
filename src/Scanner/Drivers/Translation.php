<?php

namespace Marshmallow\Translatable\Scanner\Drivers;

use Marshmallow\HelperFunctions\Facades\Str;
use Illuminate\Support\Collection;

class Translation
{
    /**
     * Find all of the translations in the app without translation for a given language.
     *
     * @param string $language
     *
     * @return array
     */
    public function findMissingTranslations($language)
    {
        return $this->arrayDiffAssocRecursive(
            $this->scanner->findTranslations(),
            $this->allTranslationsFor($language)
        );
    }

    /**
     * Recursively diff two arrays.
     *
     * @param array $arrayOne
     * @param array $arrayTwo
     *
     * @return array
     */
    public function arrayDiffAssocRecursive($arrayOne, $arrayTwo)
    {
        $difference = [];
        foreach ($arrayOne as $key => $value) {
            if (is_array($value) || $value instanceof Collection) {
                if (!isset($arrayTwo[$key])) {
                    $difference[$key] = $value;
                } elseif (!(is_array($arrayTwo[$key]) || $arrayTwo[$key] instanceof Collection)) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->arrayDiffAssocRecursive($value, $arrayTwo[$key]);
                    if (false != $new_diff) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!isset($arrayTwo[$key])) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    /**
     * Save all of the translations in the app without translation for a given language.
     *
     * @param string $language
     *
     * @return void
     */
    public function saveMissingTranslations($language = false)
    {
        $languages = $language ? [$language => $language] : $this->allLanguages();

        foreach ($languages as $language => $name) {
            $missingTranslations = $this->findMissingTranslations($language);

            foreach ($missingTranslations as $type => $groups) {
                foreach ($groups as $group => $translations) {
                    foreach ($translations as $key => $value) {
                        if (Str::contains($group, 'single')) {
                            $this->addSingleTranslation($language, $group, $key);
                        } else {
                            $this->addGroupTranslation($language, $group, $key);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get all translations for a given language merged with the source language.
     *
     * @param string $language
     *
     * @return Collection
     */
    public function getSourceLanguageTranslationsWith($language)
    {
        $sourceTranslations = $this->allTranslationsFor($this->sourceLanguage);
        $languageTranslations = $this->allTranslationsFor($language);

        return $sourceTranslations->map(function ($groups, $type) use ($language, $languageTranslations) {
            return $groups->map(function ($translations, $group) use ($type, $language, $languageTranslations) {
                $translations = $translations->toArray();
                array_walk($translations, function (&$value, &$key) use ($type, $group, $language, $languageTranslations) {
                    $value = [
                        $this->sourceLanguage => $value,
                        $language => $languageTranslations->get($type, collect())->get($group, collect())->get($key),
                    ];
                });

                return $translations;
            });
        });
    }

    /**
     * Filter all keys and translations for a given language and string.
     *
     * @param string $language
     * @param string $filter
     *
     * @return Collection
     */
    public function filterTranslationsFor($language, $filter)
    {
        $allTranslations = $this->getSourceLanguageTranslationsWith(($language));
        if (!$filter) {
            return $allTranslations;
        }

        return $allTranslations->map(function ($groups, $type) use ($language, $filter) {
            return $groups->map(function ($keys, $group) use ($language, $filter, $type) {
                return collect($keys)->filter(function ($translations, $key) use ($group, $language, $filter, $type) {
                    return Str::anyContains([$group, $key, $translations[$language], $translations[$this->sourceLanguage]], $filter);
                });
            })->filter(function ($keys) {
                return $keys->isNotEmpty();
            });
        });
    }
}

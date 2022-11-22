<?php

namespace Marshmallow\Translatable\Scanner\Drivers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Marshmallow\Translatable\Events\TranslationAdded;

class Translation
{
    /**
     * Find all of the translations in the app without translation for a given language.
     *
     * @param  string  $language
     * @return array
     */
    public function findMissingTranslations($language)
    {
        return array_diff_assoc_recursive(
            $this->scanner->findTranslations(),
            $this->allTranslationsFor($language)
        );
    }

    /**
     * Save all of the translations in the app without translation for a given language.
     *
     * @param  string  $language
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
                        $key = stripslashes($key);
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
     * @param  string  $language
     * @return Collection
     */
    public function getSourceLanguageTranslationsWith($language)
    {
        $sourceTranslations = $this->allTranslationsFor($this->sourceLanguage);
        $languageTranslations = $this->allTranslationsFor($language);

        return $sourceTranslations->map(function ($groups, $type) use ($language, $languageTranslations) {
            return $groups->map(function ($translations, $group) use ($type, $language, $languageTranslations) {
                $translations = $translations->toArray();
                array_walk($translations, function (&$value, $key) use ($type, $group, $language, $languageTranslations) {
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
     * @param  string  $language
     * @param  string  $filter
     * @return Collection
     */
    public function filterTranslationsFor($language, $filter)
    {
        $allTranslations = $this->getSourceLanguageTranslationsWith(($language));
        if (!$filter) {
            return $allTranslations;
        }

        return $allTranslations->map(function ($groups, $type) use ($language, $filter) {
            return $groups->map(function ($keys, $group) use ($language, $filter) {
                return collect($keys)->filter(function ($translations, $key) use ($group, $language, $filter) {
                    return strs_contain([$group, $key, $translations[$language], $translations[$this->sourceLanguage]], $filter);
                });
            })->filter(function ($keys) {
                return $keys->isNotEmpty();
            });
        });
    }
    /**
     * If a translation exists for EN but not for NL, this method
     * will create it.
     */
    public function createTranslationsForAllLanguages()
    {
        $languages = config('translatable.models.language')::get();
        $translations = config('translatable.models.translation')
            ::select('*', DB::raw('count(*) as total'))
            ->groupBy(['key', 'group'])
            ->get();

        foreach ($translations as $translation) {
            if ($languages->count() == $translation->total) {
                continue;
            }
            foreach ($languages as $language) {
                $value = ('single' !== $translation->group) ? $translation->value : null;

                if (!$this->translationExists($language->language, $translation->group, $translation->key)) {
                    $this->createNewTranslation($language->language, $translation->group, $translation->key, $value);
                }
            }
        }

        return 0;
    }
}

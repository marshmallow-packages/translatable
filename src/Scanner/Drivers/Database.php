<?php

namespace Marshmallow\Translatable\Scanner\Drivers;

use Illuminate\Support\Collection;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation as TranslationModel;

class Database extends Translation implements DriverInterface
{
    protected $scanner;

    protected $getLanguages;

    protected $sourceLanguage;

    /*
     * Store translations to avoid multiple reloads of the same translations per request.
     */
    protected static $translations = [];

    public function __construct($sourceLanguage, $scanner)
    {
        $this->sourceLanguage = $sourceLanguage;
        $this->scanner = $scanner;
        $this->getLanguages = Language::cursor()->remember();
    }

    /**
     * Get all languages from the application.
     *
     * @return Collection
     */
    public function allLanguages()
    {
        return Language::all()->mapWithKeys(function ($language) {
            return [$language->language => $language->name ?: $language->language];
        });
    }

    /**
     * Get all group translations from the application.
     *
     * @return Collection
     */
    public function allGroup(string $language)
    {
        $groups = TranslationModel::getGroupsForLanguage($language);

        return $groups->map(function ($translation) {
            return $translation->group;
        });
    }

    /**
     * Get all the translations from the application.
     *
     * @return Collection
     */
    public function allTranslations()
    {
        return $this->allLanguages()->mapWithKeys(function ($name, $language) {
            return [$language => $this->allTranslationsFor($language)];
        });
    }

    /**
     * Get all translations for a particular language.
     *
     * @return Collection
     */
    public function allTranslationsFor(string $language)
    {
        return Collection::make([
            'group' => $this->getGroupTranslationsFor($language),
            'single' => $this->getSingleTranslationsFor($language),
        ]);
    }

    /**
     * Add a new language to the application.
     *
     * @param string $language
     *
     * @return void
     */
    public function addLanguage($language, $name = null)
    {
        if ($this->languageExists($language)) {
            throw new LanguageExistsException(__('translation::errors.language_exists', ['language' => $language]));
        }

        Language::create([
            'language' => $language,
            'name' => $name,
        ]);
    }

    /**
     * Add a new group type translation.
     *
     * @param string $language
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function addGroupTranslation($language, $group, $key, $value = '')
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        if (false !== strpos($group, '::')) {
            $group = explode('::', $group);
            $group = $group[1];
        }

        Language::where('language', $language)
            ->first()
            ->translations()
            ->updateOrCreate([
                'group' => $group,
                'key' => $key,
            ], [
                'group' => $group,
                'key' => $key,
                'value' => $value,
            ]);
    }

    /**
     * Add a new single type translation.
     *
     * @param string $language
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function addSingleTranslation($language, $vendor, $key, $value = '')
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        Language::where('language', $language)
                ->first()
                ->translations()
                ->updateOrCreate([
                    'group' => $vendor,
                    'key' => $key,
                ], [
                    'key' => $key,
                    'value' => $value,
                ]);
    }

    /**
     * Get all of the single translations for a given language.
     *
     * @return Collection
     */
    public function getSingleTranslationsFor(string $language)
    {
        if (! empty(self::$translations['single'][$language])) {
            return self::$translations['single'][$language];
        }

        $translations = $this->getLanguage($language)
            ->translations()
            ->where('group', 'like', '%single')
            ->orWhereNull('group')
            ->get()
            ->groupBy('group');

        /*
         * if there is no group, this is a legacy translation so we need to
         * update to 'single'. We do this here so it only happens once.
         */
        if ($this->hasLegacyGroups($translations->keys())) {
            TranslationModel::whereNull('group')->update(['group' => 'single']);

            /*
             * if any legacy groups exist, rerun the method so we get the
             * updated keys.
             */
            return $this->getSingleTranslationsFor($language);
        }

        self::$translations['single'][$language] = $translations->map(function ($translations, $group) use ($language) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });

        return self::$translations['single'][$language];
    }

    /**
     * Get all of the group translations for a given language.
     *
     * @return Collection
     */
    public function getGroupTranslationsFor(string $language)
    {
        if (! empty(self::$translations['group'][$language])) {
            return self::$translations['group'][$language];
        }

        $translations = $this->getLanguage($language)
                            ->translations()
                            ->whereNotNull('group')
                            ->where('group', 'not like', '%single')
                            ->get()
                            ->groupBy('group');

        self::$translations['group'][$language] = $translations->map(function ($translations) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });

        return self::$translations['group'][$language];
    }

    /**
     * Determine whether or not a language exists.
     *
     * @return bool
     */
    public function languageExists(string $language)
    {
        return $this->getLanguage($language) ? true : false;
    }

    /**
     * Get a collection of group names for a given language.
     *
     * @return Collection
     */
    public function getGroupsFor(string $language)
    {
        return $this->allGroup($language);
    }

    /**
     * Get a language from the database.
     *
     * @return Language
     */
    private function getLanguage(string $language)
    {
        return $this->getLanguages->where('language', $language)->first();
    }

    /**
     * Determine if a set of single translations contains any legacy groups.
     * Previously, this was handled by setting the group value to NULL, now
     * we use 'single' to cater for vendor JSON language files.
     *
     * @return bool
     */
    private function hasLegacyGroups(Collection $groups)
    {
        return $groups->filter(function ($key) {
            return '' === $key;
        })->count() > 0;
    }
}

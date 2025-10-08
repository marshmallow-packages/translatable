<?php

namespace Marshmallow\Translatable\Scanner\Drivers;

use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Marshmallow\Translatable\Exceptions\LanguageExistsException;

class Database extends Translation implements DriverInterface
{
    protected $scanner;

    protected $sourceLanguage;

    protected array $groupTranslationCache = [];

    protected array $singleTranslationCache = [];

    protected array $languageCache = [];

    /*
     * Store translations to avoid multiple reloads of the same translations per request.
     */
    protected static $translations = [];

    public function __construct($sourceLanguage, $scanner)
    {
        $this->sourceLanguage = $sourceLanguage;
        $this->scanner = $scanner;
    }

    /**
     * Get all languages from the application.
     *
     * @return Collection
     */
    public function allLanguages()
    {
        return config('translatable.models.language')::all()->mapWithKeys(function ($language) {
            return [$language->language => $language->name ?: $language->language];
        });
    }

    /**
     * Get all active languages from the application.
     *
     * @return Collection
     */
    public function allActiveLanguages()
    {
        return config('translatable.models.language')::active()->get()->mapWithKeys(function ($language) {
            return [$language->language => $language->name ?: $language->language];
        });
    }

    /**
     * Get all group translations from the application.
     *
     * @return array
     */
    public function allGroup($language)
    {
        $groups = config('translatable.models.translation')::getGroupsForLanguage($language);

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
     * @param  string  $language
     * @return Collection
     */
    public function allTranslationsFor($language)
    {
        return Collection::make([
            'group' => $this->getGroupTranslationsFor($language),
            'single' => $this->getSingleTranslationsFor($language),
        ]);
    }

    /**
     * Add a new language to the application.
     *
     * @param  string  $language
     * @return void
     */
    public function addLanguage($language, $name = null)
    {
        if ($this->languageExists($language)) {
            throw new LanguageExistsException(__('translatable::errors.language_exists', ['language' => $language]));
        }

        config('translatable.models.language')::create([
            'language' => $language,
            'name' => $name,
        ]);
    }

    /**
     * Add a new group type translation.
     *
     * @param  string  $language
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public function addGroupTranslation($language, $group, $key, $value = null)
    {
        if (!$this->languageExists($language)) {
            $this->addLanguage($language);
        }

        if (!$this->translationExists($language, $group, $key)) {
            $this->createNewTranslation($language, $group, $key, $value);
        }
    }

    /**
     * Add a new single type translation.
     *
     * @param  string  $language
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public function addSingleTranslation($language, $vendor, $key, $value = null)
    {
        if (!$this->languageExists($language)) {
            $this->addLanguage($language);
        }

        if (!$this->translationExists($language, $vendor, $key)) {
            $this->createNewTranslation($language, $vendor, $key, $value);
        }
    }

    /**
     * Save a new translation for a language.
     *
     * @param  string  $language
     * @param  string  $key
     * @param  string  $value
     * @return void
     */
    public function createNewTranslation($language, $vendor, $key, $value = null)
    {
        // TEMP KEEP THIS FOR BACKWARDS COMPATIBILITY
        // $language = config('translatable.models.language')::where('language', $language)->first();
        // config('translatable.models.translation')::create([
        //     'language_id' => $language->id,
        //     'group' => $vendor,
        //     'key' => $key,
        //     'value' => $value,
        // ]);

        $language = config('translatable.models.language')::where('language', $language)
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
     * Determine whether or not a translations exists.
     *
     * @param  string  $language
     * @param  string  $vendor
     * @param  string  $key
     * @return bool
     */
    public function translationExists($language, $vendor, $key)
    {
        return config('translatable.models.translation')
            ::join('languages', 'languages.id', '=', 'translations.language_id')
            ->where('languages.language', $language)
            ->where('translations.group', $vendor)
            ->where(DB::raw('BINARY translations.key'), $key)
            ->first();
    }

    /**
     * Get all of the single translations for a given language.
     *
     * @param  string  $language
     * @return Collection
     */
    public function getSingleTranslationsFor($language)
    {
        if (isset($this->singleTranslationCache[$language])) {
            return $this->singleTranslationCache[$language];
        }
        $languageModel = $this->getLanguage($language);
        if (!$languageModel) {
            return collect();
        }
        $translations = $languageModel
            ->translations()
            ->where('group', 'like', '%single')
            ->orWhereNull('group')
            ->get()
            ->groupBy('group');

        // if there is no group, this is a legacy translation so we need to
        // update to 'single'. We do this here so it only happens once.
        if ($this->hasLegacyGroups($translations->keys())) {
            config('translatable.models.translation')::whereNull('group')->update(['group' => 'single']);
            // if any legacy groups exist, rerun the method so we get the
            // updated keys.
            return $this->getSingleTranslationsFor($language);
        }

        $result = $translations->map(function ($translations, $group) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });

        $this->singleTranslationCache[$language] = $result;

        return $result;
    }

    /**
     * Get all of the group translations for a given language.
     *
     * @param  string  $language
     * @return Collection
     */
    public function getGroupTranslationsFor($language)
    {
        if (isset($this->groupTranslationCache[$language])) {
            return $this->groupTranslationCache[$language];
        }

        $languageModel = $this->getLanguage($language);

        if (is_null($languageModel)) {
            return collect();
        }

        $translations = $languageModel
            ->translations()
            ->whereNotNull('group')
            ->where('group', 'not like', '%single')
            ->get()
            ->groupBy('group');

        $result = $translations->map(function ($translations) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            })->sortBy('key');
        });

        $this->groupTranslationCache[$language] = $result;

        return $result;
    }

    /**
     * Determine whether or not a language exists.
     *
     * @param  string  $language
     * @return bool
     */
    public function languageExists($language)
    {
        return $this->getLanguage($language) ? true : false;
    }

    /**
     * Get a collection of group names for a given language.
     *
     * @param  string  $language
     * @return Collection
     */
    public function getGroupsFor($language)
    {
        return $this->allGroup($language);
    }

    /**
     * Get a language from the database.
     *
     * @param  string  $language
     * @return Language
     */
    private function getLanguage($language)
    {
        if (isset($this->languageCache[$language])) {
            return $this->languageCache[$language];
        }

        // Some constallation of composer packages can lead to our code being executed
        // as a dependency of running migrations. That's why we need to be able to
        // handle the case where the database is empty / our tables don't exist:
        try {
            $result = config('translatable.models.language')::where('language', $language)->first();
        } catch (Throwable) {
            $result = null;
        }

        $this->languageCache[$language] = $result;

        return $result;
    }

    /**
     * Determine if a set of single translations contains any legacy groups.
     * Previously, this was handled by setting the group value to NULL, now
     * we use 'single' to cater for vendor JSON language files.
     *
     * @param  Collection  $groups
     * @return bool
     */
    private function hasLegacyGroups($groups)
    {
        return $groups->filter(function ($key) {
            return $key === '';
        })->count() > 0;
    }
}

<?php

namespace Marshmallow\Translatable\Scanner\Drivers;

use Illuminate\Support\Collection;

interface DriverInterface
{
    /**
     * Get all languages from the application.
     *
     * @return Collection
     */
    public function allLanguages();

    /**
     * Get all group translations from the application.
     *
     * @return array
     */
    public function allGroup(string $language);

    /**
     * Get all the translations from the application.
     *
     * @return Collection
     */
    public function allTranslations();

    /**
     * Get all translations for a particular language.
     *
     * @param string $language
     *
     * @return Collection
     */
    public function allTranslationsFor($language);

    /**
     * Add a new language to the application.
     *
     * @param string $language
     *
     * @return void
     */
    public function addLanguage($language, $name = null);

    /**
     * Add a new group type translation.
     *
     * @param string $language
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function addGroupTranslation($language, $group, $key, $value = '');

    /**
     * Add a new single type translation.
     *
     * @param string $language
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function addSingleTranslation($language, $vendor, $key, $value = '');

    /**
     * Get all of the single translations for a given language.
     *
     * @return Collection
     */
    public function getSingleTranslationsFor(string $language);

    /**
     * Get all of the group translations for a given language.
     *
     * @return Collection
     */
    public function getGroupTranslationsFor(string $language);

    /**
     * Determine whether or not a language exists.
     *
     * @return bool
     */
    public function languageExists(string $language);

    /**
     * Find all of the translations in the app without translation for a given language.
     *
     * @return array
     */
    public function findMissingTranslations(string $language);

    /**
     * Save all of the translations in the app without translation for a given language.
     *
     * @return void
     */
    public function saveMissingTranslations($language = false);

    /**
     * Get a collection of group names for a given language.
     *
     * @return Collection
     */
    public function getGroupsFor(string $language);

    /**
     * Get all translations for a given language merged with the source language.
     *
     * @return Collection
     */
    public function getSourceLanguageTranslationsWith(string $language);

    /**
     * Filter all keys and translations for a given language and string.
     *
     * @return Collection
     */
    public function filterTranslationsFor(string $language, string $filter);
}

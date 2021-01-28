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
     * @return Collection
     */
    public function allTranslationsFor(string $language);

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
    public function saveMissingTranslations(string $language = '');

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

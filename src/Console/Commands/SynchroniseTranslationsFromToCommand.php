<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Marshmallow\Translatable\Scanner\Scanner;
use Marshmallow\Translatable\Scanner\Drivers\File;
use Marshmallow\Translatable\Scanner\Drivers\Database;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

class SynchroniseTranslationsFromToCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:sync-translations {from?} {to?} {language?} {--active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise translations between drivers';

    /**
     * File scanner.
     *
     * @var Scanner
     */
    private $scanner;

    /**
     * Translation.
     *
     * @var Translation
     */
    private $translation;

    /**
     * From driver.
     */
    private $fromDriver;

    /**
     * To driver.
     */
    private $toDriver;

    /**
     * Translation drivers.
     *
     * @var array
     */
    private $drivers = ['file', 'database'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Scanner $scanner, Translation $translation)
    {
        parent::__construct();
        $this->scanner = $scanner;
        $this->translation = $translation;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $onlyActive = $this->option('active') ? true : false;

        if ($onlyActive) {
            $languages = $this->translation->allActiveLanguages();
        } else {
            $languages = $this->translation->allLanguages();
        }

        $languages = array_keys($languages->toArray());

        // If a valid from driver has been specified as an argument.
        if ($this->argument('from') && in_array($this->argument('from'), $this->drivers)) {
            $this->fromDriver = $this->argument('from');
        }

        // When the from driver will be entered manually or if the argument is invalid.
        else {
            $this->fromDriver = $this->anticipate(__('translatable::translatable.prompt_from_driver'), $this->drivers);

            if (!in_array($this->fromDriver, $this->drivers)) {
                return $this->error(__('translatable::translatable.invalid_driver'));
            }
        }

        // Create the driver.
        $this->fromDriver = $this->createDriver($this->fromDriver);

        // When the to driver has been specified.
        if ($this->argument('to') && in_array($this->argument('to'), $this->drivers)) {
            $this->toDriver = $this->argument('to');
        }

        // When the to driver will be entered manually.
        else {
            $this->toDriver = $this->anticipate(__('translatable::translatable.prompt_to_driver'), $this->drivers);

            if (!in_array($this->toDriver, $this->drivers)) {
                return $this->error(__('translatable::translatable.invalid_driver'));
            }
        }

        // Create the driver.
        $this->toDriver = $this->createDriver($this->toDriver);

        // If the language argument is set.
        if ($this->argument('language')) {

            // If all languages should be synced.
            if ($this->argument('language') == 'all') {
                $language = false;
            }
            // When a specific language is set and is valid.
            elseif (in_array($this->argument('language'), $languages)) {
                $language = $this->argument('language');
            } else {
                return $this->error(__('translatable::translatable.invalid_language'));
            }
        } // When the language will be entered manually or if the argument is invalid.
        else {
            $language = $this->anticipate(__('translatable::translatable.prompt_language_if_any'), $languages);

            if ($language && !in_array($language, $languages)) {
                return $this->error(__('translatable::translatable.invalid_language'));
            }
        }

        $this->line(__('translatable::translatable.syncing'));

        // If a specific language is set.
        if ($language) {
            $this->mergeTranslations($this->toDriver, $language, $this->fromDriver->allTranslationsFor($language));
        } // Else process all languages.
        else {
            if ($onlyActive) {
                $languages = $this->toDriver->allActiveLanguages();
            } else {
                $languages = $this->toDriver->allLanguages();
            }
            $translations = $this->mergeLanguages($this->toDriver, $languages);
        }

        $this->info(__('translatable::translatable.synced'));
    }

    private function createDriver($driver)
    {
        if ($driver === 'file') {
            return new File(new Filesystem(), app('path.lang'), config('app.locale'), $this->scanner);
        }

        return new Database(config('app.locale'), $this->scanner);
    }

    private function mergeLanguages($driver, $languages)
    {
        foreach ($languages as $language => $language_name) {
            $translations = $this->fromDriver->allTranslationsFor($language);
            $this->mergeTranslations($driver, $language, $translations);
        }
    }

    private function mergeTranslations($driver, $language, $translations)
    {
        $this->mergeGroupTranslations($driver, $language, $translations['group']);
        $this->mergeSingleTranslations($driver, $language, $translations['single']);
    }

    private function mergeGroupTranslations($driver, $language, $groups)
    {
        foreach ($groups as $group => $translations) {
            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $driver->addGroupTranslation($language, $group, $key, $value);
            }
        }
    }

    private function mergeSingleTranslations($driver, $language, $vendors)
    {
        foreach ($vendors as $vendor => $translations) {
            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $driver->addSingleTranslation($language, $vendor, $key, $value);
            }
        }
    }
}

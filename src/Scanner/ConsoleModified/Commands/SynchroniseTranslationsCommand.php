<?php

namespace Marshmallow\Translatable\Scanner\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Marshmallow\Translatable\Scanner\Drivers\Database;
use Marshmallow\Translatable\Scanner\Drivers\File;
use Marshmallow\Translatable\Scanner\Drivers\Translation;
use Marshmallow\Translatable\Scanner\Scanner;

class SynchroniseTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:sync-file-to-database {language?}';

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
        $languages = array_keys($this->translation->allLanguages()->toArray());

        $this->fromDriver = 'file';

        // Create the driver.
        $this->fromDriver = $this->createDriver($this->fromDriver);

        // When the to driver has been specified.
        $this->toDriver = 'database';

        // Create the driver.
        $this->toDriver = $this->createDriver($this->toDriver);

        $this->line(__('🌊 We are sinking, just kidding, we are syncing...'));

        $translations = $this->mergeLanguages($this->toDriver, $this->fromDriver->allTranslations());

        $this->info(__('🎁 Translations have been synced from your files to the database successfully.'));
    }

    private function createDriver($driver)
    {
        if ($driver === 'file') {
            return new File(new Filesystem, app('path.lang'), config('app.locale'), $this->scanner);
        }

        return new Database(config('app.locale'), $this->scanner);
    }

    private function mergeLanguages($driver, $languages)
    {
        foreach ($languages as $language => $translations) {
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

<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Marshmallow\Translatable\Scanner\Scanner;
use Marshmallow\Translatable\Scanner\Drivers\File;
use Marshmallow\Translatable\Scanner\Drivers\Database;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

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
    protected $description = 'Synchronise translations from your language files to the database';

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

        // Create the driver.
        $this->fromDriver = 'file';

        // Create the driver.
        $this->toDriver = 'database';

        if ($this->argument('language')) {

            // If all languages should be synced.
            if ($this->argument('language') == 'all') {
                $language = 'all';
            }
            // When a specific language is set and is valid.
            elseif (in_array($this->argument('language'), $languages)) {
                $language = $this->argument('language');
            } else {
                return $this->error(__('translatable::translatable.invalid_language'));
            }
        } // When the language will be entered manually or if the argument is invalid.
        else {
            $language = 'all';
        }

        // Sync the translations.
        $this->call('translatable:sync-translations', [
            'from' => 'file',
            'to' => 'database',
            'language' => $language,
        ]);

        $this->info(__('We recommend you run "php artisan translatable:sync-missing" to make sure the new translations are avaialable in all your languages.'));
    }
}

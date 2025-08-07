<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Marshmallow\Translatable\Scanner\Drivers\Translation;

class SynchroniseTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:sync-file-to-database {language?} {--active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise translations from your language files to the database';

    /**
     * Translation.
     *
     * @var Translation
     */
    private $translation;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Translation $translation)
    {
        parent::__construct();
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
            '--active' => $onlyActive,
        ]);

        $this->info(__('translatable::translatable.prompt_recommend'));
    }
}

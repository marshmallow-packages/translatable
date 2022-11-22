<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Support\Facades\DB;
use Marshmallow\Translatable\Console\Commands\BaseCommand;

class SynchroniseMissingTranslationKeys extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:sync-missing {language?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all of the missing translation keys for all languages or a single language';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $language = $this->argument('language') ?: false;

        try {
            $this->line(__('translatable::translatable.syncing'));
            // if we have a language, pass it in, if not the method will
            // automagically sync all languages
            $this->translation->saveMissingTranslations($language);

            // TEMP
            // $this->translation->createTranslationsForAllLanguages();

            return $this->info(__('translatable::translatable.keys_synced'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

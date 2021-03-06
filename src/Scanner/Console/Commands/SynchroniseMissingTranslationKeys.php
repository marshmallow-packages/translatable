<?php

namespace Marshmallow\Translatable\Scanner\Console\Commands;

use Illuminate\Support\Facades\DB;

class SynchroniseMissingTranslationKeys extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:sync-missing';

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
        try {
            $this->translation->saveMissingTranslations();
            $this->translation->createTranslationsForAllLanguages();

            return $this->info('✅ Saving missing translations found in your code to the database');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

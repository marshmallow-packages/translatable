<?php

namespace Marshmallow\Translatable\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the translatable package. This will migrate your database and seed the tables.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->syncFilesToDatabase();
        $this->syncInlineTranslartions();
        $this->generateNovaResources();
    }

    protected function generateNovaResources()
    {
        $this->info('Generating nova resources');
        Artisan::call('marshmallow:resource Language Translatable');
        Artisan::call('marshmallow:resource Translation Translatable');

        $this->info('Language and Translation resources have been created and are available in Nova');
    }

    protected function syncInlineTranslartions()
    {
        $this->info('Collecting all other translation from the paths provided in your config file');
        Artisan::call('translatable:sync-missing');
        $this->info('All translations are now available');
    }

    protected function syncFilesToDatabase()
    {
        $this->info('Starting migrating language files to database');
        Artisan::call('translation:sync-translations file database');
        $this->info('Translation files are now available in the translations table.');
    }
}

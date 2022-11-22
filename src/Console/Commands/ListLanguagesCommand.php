<?php

namespace Marshmallow\Translatable\Console\Commands;

use Marshmallow\Translatable\Console\Commands\BaseCommand;

class ListLanguagesCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:list-languages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the available languages in the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $headers = [__('translatable::translation.language_name'), __('translatable::translation.language')];
        $languages = $this->translation->allLanguages()->toArray();
        $mappedLanguages = [];

        foreach ($languages as $language => $name) {
            $mappedLanguages[] = [$name, $language];
        }

        // return a table of results
        $this->table($headers, $mappedLanguages);
    }
}

<?php

namespace Marshmallow\Translatable\Console\Commands;

use Marshmallow\Translatable\Console\Commands\BaseCommand;

class AddLanguageCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:add-language';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new language to the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // ask the user for the language they wish to add
        $language = $this->ask(__('translatable::translatable.prompt_language'));
        $name = $this->ask(__('translatable::translatable.prompt_name'));

        // attempt to add the key and fail gracefully if exception thrown
        try {
            $this->translation->addLanguage($language, $name);
            $this->info(__('translatable::translatable.language_added'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

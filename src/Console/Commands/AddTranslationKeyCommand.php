<?php

namespace Marshmallow\Translatable\Console\Commands;

use Marshmallow\Translatable\Console\Commands\BaseCommand;

class AddTranslationKeyCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:add-translation-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new language key for the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $language = $this->ask(__('translatable::translation.prompt_language_for_key'));

        // we know this should be single or group so we can use the `anticipate`
        // method to give our users a helping hand
        $type = $this->anticipate(__('translatable::translation.prompt_type'), ['single', 'group']);

        // if the group type is selected, prompt for the group key
        if ('group' === $type) {
            $file = $this->ask(__('translatable::translation.prompt_group'));
        }
        $key = $this->ask(__('translatable::translation.prompt_key'));
        $value = $this->ask(__('translatable::translation.prompt_value'));

        // attempt to add the key for single or group and fail gracefully if
        // exception is thrown
        if ('single' === $type) {
            try {
                $this->translation->addSingleTranslation($language, 'single', $key, $value);

                return $this->info(__('translatable::translation.language_key_added'));
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } elseif ('group' === $type) {
            try {
                $file = str_replace('.php', '', $file);
                $this->translation->addGroupTranslation($language, $file, $key, $value);

                return $this->info(__('translatable::translation.language_key_added'));
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->error(__('translatable::translation.type_error'));
        }
    }
}

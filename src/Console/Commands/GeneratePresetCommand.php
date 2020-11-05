<?php

namespace Marshmallow\Translatable\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Marshmallow\HelperFunctions\Facades\Arrayable;
use Marshmallow\Translatable\Models\Language;

class GeneratePresetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:generate-preset {language?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a preset from our packages database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('language')) {
            $languages = Language::where('language', $this->argument('language'))->get();
        } else {
            $language = $this->choice(
                __('Please select a language for which you wish to generate a preset.'),
                $this->getAvailableLanguagesArray(),
                'all'
            );

            if ('all' === $language) {
                $languages = Language::get();
            } else {
                $languages = Language::where('language', $language)->get();
            }
        }

        if (!$languages->count()) {
            throw new Exception(__('No language found to build a preset for.'));
        }

        foreach ($languages as $language) {
            $generated_preset = [
                'locale' => $language->language,
                'name' => $language->getTranslation('name', $language->language),
                'preset' => $language->getPreset(),
            ];

            Arrayable::storeInFile(
                $generated_preset,
                $this->getPresetFolderPath()."/$language->language.php"
            );
        }
    }

    private function getPresetFolderPath()
    {
        return __DIR__.'/../../../resources/presets';
    }

    private function getAvailableLanguagesArray()
    {
        $languages = Language::get()->pluck('name', 'language')->toArray();
        $languages['all'] = __('All');

        return $languages;
    }
}

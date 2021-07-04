<?php

namespace Marshmallow\Translatable\Console\Commands;

use Exception;
use Illuminate\Console\Command;

class PresetCommand extends Command
{
    protected $preset;

    protected $language;

    protected $untranslated_items = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translatable:preset {language} {?--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill your database with our preset so you dont need to translate everything.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*
         * Make sure we have a valid lanugage provided in the command
         */
        $this->fillLanguageProperty();

        /*
         * Get the correct preset that the user wishes to import.
         */
        $this->fillPresetProperty();

        /*
         * Check if we interpreted everything correctly.
         */
        if (!$this->importContextIsCorrect()) {
            return $this->stopImporter();
        }

        /*
         * Check if there is something to import or not.
         */
        if (!$this->checkIfThereIsSomethingToImport()) {
            return $this->stopImporter();
        }

        /*
         * Ask the user if they wish to see the values that will
         * be imported in a table in there terminal.
         */
        $this->askAndShowTheImportDataInATable();

        /*
         * Continue with the import!!
         */
        if (!$this->option('force') && !$this->confirm(__('Please type "yes" to start the import'))) {
            return $this->stopImporter();
        }

        $this->importThePresetToTheDatabase();
    }

    private function importThePresetToTheDatabase()
    {
        $this->output->progressStart(count($this->untranslated_items));

        foreach ($this->untranslated_items as $preset) {
            config('translatable.models.translation')::updateOrCreate([
                'group' => $preset['group'],
                'key' => $preset['key'],
                'language_id' => $this->language->id,
            ], [
                'value' => $preset['value'],
            ]);

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info(__('The preset is imported successfully 😃'));
    }

    private function askAndShowTheImportDataInATable()
    {
        if ($this->option('force')) {
            return;
        }
        if ($this->confirm(__('Do you wish to see a table with the content we will be importing?'))) {
            $this->table([__('Group'), __('Key'), __('Translation')], $this->untranslated_items);
        }
    }

    private function checkIfThereIsSomethingToImport()
    {
        $this->info('Matching preset to your database.');
        $this->untranslated_items = $this->buildUntranslatedPresetArray();
        if (0 === count($this->untranslated_items)) {
            $this->info('There is nothing to import for you.');

            return false;
        }

        return true;
    }

    private function fillLanguageProperty()
    {
        $language = $this->argument('language');
        $this->language = config('translatable.models.language')::where('language', $language)->firstOrFail();
    }

    private function fillPresetProperty()
    {
        $preset_path = $this->getPresetFilePath($this->language->language);
        if (!file_exists($preset_path)) {
            $preset = $this->choice(
                __('We couldnt match a preset. Which preset do you wish to import?'),
                $this->getAvailablePresetArray()
            );

            $preset_path = $this->getPresetFilePath($preset);
        }

        if (!file_exists($preset_path)) {
            throw new Exception(__('Preset is not available. Please try again.'));
        }

        $this->preset = $this->getPreset($preset_path);
    }

    private function importContextIsCorrect()
    {
        if ($this->option('force')) {
            return true;
        }

        $confirm_text = __('We are going to import our ":preset_name" into your database connected to the language ":local_language_name". Is this correct?', [
            'preset_name' => 'Preset ' . $this->preset['name'],
            'local_language_name' => $this->language->name,
        ]);

        if (!$this->confirm($confirm_text)) {
            return false;
        }

        return true;
    }

    private function stopImporter()
    {
        $this->error(__('Importing preset has been stopped.'));

        return 0;
    }

    private function buildUntranslatedPresetArray()
    {
        $untranslated_items = [];
        $this->output->progressStart(count($this->preset['preset']));
        foreach ($this->preset['preset'] as $translation_preset) {
            $translation = config('translatable.models.translation')::where('language_id', $this->language->id)
                ->where('group', $translation_preset['group'])
                ->where('key', $translation_preset['key'])
                ->first();

            if (!$translation || !$translation->value) {
                $untranslated_items[] = $translation_preset;
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();

        $this->info(
            __('We have found :count preset value(s) that we can fill for you.', [
                'count' => count($untranslated_items),
            ])
        );

        return $untranslated_items;
    }

    private function getPreset(string $preset_file_path)
    {
        $preset = include $preset_file_path;

        return $preset;
    }

    private function getPresetFilePath(string $language_code)
    {
        return $this->getPresetFolderPath() . "/$language_code.php";
    }

    private function getPresetFolderPath()
    {
        return __DIR__ . '/../../../resources/presets';
    }

    private function getAvailablePresetArray()
    {
        $presets = [];
        $files = glob($this->getPresetFolderPath() . '/*.{php}', GLOB_BRACE);
        foreach ($files as $file) {
            $preset = $this->getPreset($file);
            $presets[$preset['locale']] = $preset['name'];
        }

        return $presets;
    }
}
